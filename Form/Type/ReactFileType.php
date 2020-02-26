<?php

declare(strict_types=1);

namespace Arthem\Bundle\FileBundle\Form\Type;

use Arthem\Bundle\FileBundle\EventListener\UploadableListener;
use Arthem\Bundle\FileBundle\Model\FileInterface;
use Arthem\Bundle\FileBundle\Model\FileWrapperInterface;
use Arthem\Bundle\FileBundle\Validator\File;
use Doctrine\ORM\EntityManager;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as BaseFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ReactFileType extends AbstractType
{
    protected $class;

    /**
     * @var UploadableListener
     */
    protected $uploadableListener;

    /**
     * @var EntityManager
     */
    protected $om;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $defaultFilterName;

    /**
     * @var string
     */
    protected $defaultOriginFilterName;

    /**
     * @var int
     */
    protected $defaultPreviewWidth;

    /**
     * @var int
     */
    protected $defaultPreviewHeight;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var RegistryInterface
     */
    private $registry;
    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct(
        $class,
        RegistryInterface $registry,
        UploadableListener $uploadableListener,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        TranslatorInterface $translator,
        $defaultFilterName,
        $defaultOriginFilterName,
        $defaultPreviewWidth,
        $defaultPreviewHeight,
        ?CacheManager $cacheManager = null
    ) {
        $this->class = $class;
        $this->om = $registry->getManagerForClass($class);
        $this->uploadableListener = $uploadableListener;
        $this->router = $router;
        $this->translator = $translator;
        $this->defaultFilterName = $defaultFilterName;
        $this->defaultOriginFilterName = $defaultOriginFilterName;
        $this->defaultPreviewWidth = $defaultPreviewWidth;
        $this->defaultPreviewHeight = $defaultPreviewHeight;
        $this->authorizationChecker = $authorizationChecker;
        $this->registry = $registry;
        $this->cacheManager = $cacheManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $csrfProtection = isset($options['csrf_token_manager']);
        $fileAttr = [];
        if ($options['capture']) {
            $fileAttr['capture'] = true;
        }
        if (null !== $options['accept']) {
            $fileAttr['accept'] = implode(',', $options['accept']);
        }

        $fileInput = $builder->create('file', BaseFileType::class, [
            'mapped' => false,
            'multiple' => $options['multiple'],
            'attr' => $fileAttr,
            'constraints' => [
                new File([
                    'mimeTypes' => $options['accept'],
                    'multiple' => $options['multiple'],
                ]),
            ],
        ]);
        $idInput = $builder->create('id', HiddenType::class, [
            'mapped' => false,
        ]);

        if ($csrfProtection) {
            /** @var CsrfTokenManagerInterface $csrfTokenManager */
            $csrfTokenManager = $options['csrf_token_manager'];
            $token = $csrfTokenManager->getToken('file')->getValue();
        } else {
            $token = null;
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($csrfProtection, $options, $token) {
            $config = $event->getForm()->getConfig();
            $multiple = $config->getOption('multiple');
            $data = $event->getForm()->get('file')->getData();

            $handleFile = function ($data) use ($csrfProtection, $token, $options) {
                if ($data instanceof UploadedFile) {
                    /** @var FileInterface $file */
                    $file = new $this->class();
                    $file->setFile($data);
                    if ($csrfProtection) {
                        $file->setToken($token);
                    }
                    if ($options['user_id']) {
                        $file->setUserId($options['user_id']);
                    }

                    return $file;
                } else {
                    return null;
                }
            };

            if ($multiple && $data) {
                $d = [];
                foreach ($data as $file) {
                    $f = $handleFile($file);
                    if (null !== $f) {
                        $d[] = $f;
                    }
                }
            } else {
                $d = $handleFile($data);
            }

            $event->setData($d);
        });

        if ($options['multiple']) {
            $fileInput->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                if (null === $event->getData()) {
                    $event->setData([]);
                }
            }, 100);
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($csrfProtection, $token) {
            $config = $event->getForm()->getConfig();
            $multiple = $config->getOption('multiple');

            $originData = $event->getForm()->getData();
            $fileInput = $event->getForm()->get('file');
            $idInput = $event->getForm()->get('id');

            $data = $idInput->getData();
            if ($multiple) {
                $files = [];
                if ($data) {
                    /** @var FileInterface[] $result */
                    $result = $this->om->getRepository($this->class)
                        ->createQueryBuilder('t')
                        ->where('t.id IN (:id)')
                        ->setParameter('id', explode(',', $data))
                        ->getQuery()
                        ->getResult();

                    foreach ($result as $file) {
                        if ($csrfProtection && $token !== $file->getToken() && !$originData->contains($file)) {
                            throw new AccessDeniedHttpException('Invalid file token');
                        }
                        $files[] = $file;
                    }
                }

                $event->setData($files);
            } else {
                if ($fileInput->getData() instanceof UploadedFile) {
                    // Valid case
                } elseif ($data) {
                    $file = $this->om->find($this->class, $data);
                    if ($file instanceof FileInterface) {
                        if ($csrfProtection && $originData !== $file && $token !== $file->getToken()) {
                            throw new AccessDeniedHttpException('Invalid file token');
                        }
                        $event->setData($file);
                    }
                } else {
                    $event->setData(null);
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use (&$options) {
            $idInput = $event->getForm()->get('id');
            if ($options['multiple']) {
                /** @var FileInterface[] $files */
                if ($files = $event->getData()) {
                    $ids = [];
                    foreach ($files as $file) {
                        $ids[] = $file->getId();
                    }
                    $idInput->setData(implode(',', $ids));
                }
            } else {
                $file = $event->getData();
                if ($file instanceof FileInterface) {
                    $idInput->setData($file->getId());
                }
            }
        });

        $builder->add($fileInput);
        $builder->add($idInput);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
            'intention' => 'file',
            'browse_label' => 'form.file.browse',
            'drag_label' => 'form.file.drag',
            'browse_translation_domain' => 'ArthemFileBundle',
            'display_preview_name' => false,
            'data_class' => function (Options $options) {
                if ($options['multiple']) {
                    return null;
                } else {
                    return $this->class;
                }
            },
            'filter_name' => $this->defaultFilterName,
            'origin_filter_name' => $this->defaultOriginFilterName,
            'crop' => false,
            'ajax' => false,
            'user_id' => null,
            'target_selector' => null,
            'display_placeholder' => false,
            'preview_width' => $this->defaultPreviewWidth,
            'preview_height' => $this->defaultPreviewHeight,
            'upload_route_name' => 'arthem_file_file_upload',
            'open_route_name' => null,
            'url' => function (Options $options) {
                return $this->router->generate($options['upload_route_name']);
            },
            'delete_route' => null,
            'remove_file_label' => 'form.remove_file.label',
            'remove_file_confirm_label' => 'form.remove_file.confirm',
            'unknown_error_message' => 'form.unknown_error_message',
            'pending_uploads_label' => 'form.pending_uploads',
            'multiple' => false,
            'required' => false,
            'capture' => false,
            'accept' => [
                'image/jpeg',
                'image/png',
                'image/gif',
            ],
        ]);
    }

    /**
     * Pass the file URL to the view.
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $csrfProtection = isset($options['csrf_token_manager']);
        $jsOptions = [
            'confirmDeleteLabel' => $this->translate($options['remove_file_confirm_label'], [], $options['browse_translation_domain']),
            'browseButtonLabel' => $this->translate($options['browse_label'], [], $options['browse_translation_domain']),
            'dragLabel' => trim($this->translate($options['drag_label'], [], $options['browse_translation_domain'])),
        ];
        if ($options['ajax']) {
            $rootForm = $form->getRoot();

            $uploadParams = [];
            if ($options['filter_name']) {
                $uploadParams['filter_name'] = $options['filter_name'];
            }
            if ($options['origin_filter_name']) {
                $uploadParams['origin_filter_name'] = $options['origin_filter_name'];
            }
            if ($csrfProtection) {
                $uploadParams['file[_token]'] = (string) $rootForm->getConfig()->getOption('csrf_token_manager')->getToken('file');
            }

            $jsOptions += [
                'uploadUrl' => $options['url'],
                'fieldName' => 'file[file][file]',
                'idFieldName' => $view->vars['full_name'].'[id]',
                'uploadParams' => $uploadParams,
            ];

            if ($options['delete_route']) {
                $jsOptions['deleteUrl'] = $this->router->generate($options['delete_route']);
            }
        }

        $data = $form->getData();
        $filesInfo = [];
        if (null !== $data) {
            $files = $options['multiple'] ? $data : [$data];
            foreach ($files as $file) {
                if ($file instanceof FileWrapperInterface) {
                    $file = $file->getWrappedFile();
                }
                if ($file instanceof FileInterface) {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    $mimeType = $accessor->getValue($file, 'mimeType');
                    $path = $accessor->getValue($file, 'path');
                    $originalFilename = $accessor->getValue($file, 'originalFilename');

                    $isImage = 1 === preg_match('#^image/(gif|png|jpe?g|svg(\+xml)?)$#', $mimeType, $regs);
                    $isSVG = $isImage && 0 === strpos($regs[1], 'svg');
                    $url = null;

                    $generateOpenUrl = function () use ($file, $options): ?string {
                        if (null === $options['open_route_name']) {
                            return null;
                        }

                        return $this->router->generate($options['open_route_name'], [
                            'fileId' => $file->getId(),
                        ]);
                    };

                    if ($isImage && $options['origin_filter_name']) {
                        if ($isSVG) {
                            $url = $generateOpenUrl();
                        } else {
                            $url = $this->cacheManager->getBrowserPath($path, $options['origin_filter_name']);
                        }
                    } else {
                        $url = $generateOpenUrl();
                    }

                    $fileInfo = [
                        'id' => $file->getId(),
                        'url' => $url,
                        'name' => $originalFilename,
                        'type' => $mimeType,
                        'size' => $file->getSize(),
                    ];

                    if ($isImage) {
                        if ($isSVG) {
                            $fileInfo['thumbnail_url'] = $url;
                        } else {
                            $fileInfo['thumbnail_url'] = $this
                                ->cacheManager
                                ->getBrowserPath($path, $options['filter_name']);
                        }
                    }

                    $filesInfo[] = $fileInfo;
                }
            }
        }
        $jsOptions['documents'] = $filesInfo;

        $jsOptions['multiple'] = $options['multiple'];

        $view->vars['js_options'] = json_encode($jsOptions);
    }

    private function translate($id, array $parameters = [], $domain)
    {
        return $this->translator->trans($id, $parameters, $domain);
    }

    public function getBlockPrefix()
    {
        return 'arthem_react_file';
    }
}
