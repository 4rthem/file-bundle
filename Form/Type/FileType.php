<?php

namespace Arthem\Bundle\FileBundle\Form\Type;

use Arthem\Bundle\FileBundle\EventListener\UploadableListener;
use Arthem\Bundle\FileBundle\Model\FileInterface;
use Arthem\Bundle\FileBundle\Validator\File;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
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

class FileType extends AbstractType
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

    public function __construct(
        $class,
        ManagerRegistry $registry,
        UploadableListener $uploadableListener,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        TranslatorInterface $translator,
        $defaultFilterName,
        $defaultOriginFilterName,
        $defaultPreviewWidth,
        $defaultPreviewHeight
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
                if (null === $event->getData()) {
                    $event->setData($files);
                }
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
            'url' => function (Options $options) {
                return $this->router->generate($options['upload_route_name']);
            },
            'remove_file_label' => 'form.remove_file.label',
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
            'icons_classes' => [
                'image/.+' => 'fa fa-file-picture-o',
                'application/g?zip' => 'fa fa-file-archive-o',
                'video/.+' => 'fa fa-file-video-o',
                'application/(msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document)' => 'fa fa-file-word-o',
                'application/(excel|vnd\.openxmlformats-officedocument\.spreadsheetml\.sheet)' => 'fa fa-file-excel-o',
                'audio/.+' => 'fa fa-file-sound-o',
                'text/(php|javascript|html|x-shockwave-flash)' => 'fa fa-file-code-o',
                'text/plain' => 'fa fa-file-text-o',
                'application/pdf' => 'fa fa-file-pdf-o',
                'application/powerpoint' => 'fa fa-file-powerpoint-o',
                '.+' => 'fa fa-file-o',
            ],
        ]);

        $resolver->setAllowedTypes('icons_classes', [
            'array',
        ]);

        $resolver->setNormalizer('crop', function (Options $options, $value) {
            if (true === $value && !$options['ajax']) {
                throw new \InvalidArgumentException('"ajax" must be enabled with "crop"');
            }

            return $value;
        });
    }

    /**
     * Pass the file URL to the view.
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $csrfProtection = isset($options['csrf_token_manager']);
        $view->vars['crop'] = $options['crop'];
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['display_placeholder'] = $options['display_placeholder'];
        if ($options['display_placeholder']) {
            $view->vars['parent_class'] = $form->getParent()->getConfig()->getDataClass();
        }
        $view->vars['remove_file_label'] = $options['remove_file_label'];
        $view->vars['filter_name'] = $options['filter_name'];
        $view->vars['display_preview'] = null === $options['target_selector'];
        $view->vars['browse_label'] = $options['browse_label'];
        $view->vars['browse_translation_domain'] = $options['browse_translation_domain'];
        $view->vars['display_preview_name'] = $options['display_preview_name'];
        $view->vars['origin_filter_name'] = $options['origin_filter_name'];
        if ($options['multiple']) {
            $view->vars['attr']['multiple'] = 'multiple';
        }

        $jsOptions = [];
        if ($options['ajax']) {
            $rootForm = $form->getRoot();
            if ($csrfProtection) {
                $token = (string) $rootForm->getConfig()->getOption('csrf_token_manager')->getToken('file');
            } else {
                $token = null;
            }

            $jsOptions = [
                'ajax' => true,
                'url' => $options['url'],
                'token' => $token,
                'remove_file_label' => $this->translate($options['remove_file_label']),
                'unknown_error_message' => $this->translate($options['unknown_error_message']),
                'target_selector' => $options['target_selector'],
                'preview_width' => $options['preview_width'],
                'preview_height' => $options['preview_height'],
                'icon_classes' => $options['icons_classes'],
                'pending_uploads_label' => $this->translate($options['pending_uploads_label']),
            ];
            if ($options['filter_name']) {
                $jsOptions['filter_name'] = $options['filter_name'];
            }
            if ($options['origin_filter_name']) {
                $jsOptions['origin_filter_name'] = $options['origin_filter_name'];
            }
        }

        if ($options['crop']) {
            $jsOptions['crop'] = true;
            $jsOptions['crop_options'] = [
                'cropUrl' => $this->router->generate('arthem_file_image_crop'),
            ];
        }

        $jsOptions['multiple'] = $options['multiple'];

        $view->vars['js_options'] = json_encode($jsOptions);
        if ($options['origin_filter_name']) {
            $view->vars['origin_filter_name'] = $options['origin_filter_name'];
        }

        $data = $form->getData();

        $filesInfo = [];
        if (null !== $data) {
            $iconClasses = $options['icons_classes'];
            $files = $options['multiple'] ? $data : [$data];
            foreach ($files as $file) {
                if ($file instanceof FileInterface) {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    $mimeType = $accessor->getValue($file, 'mimeType');
                    $path = $accessor->getValue($file, 'path');

                    $filesInfo[] = [
                        'id' => $file->getId(),
                        'url' => $path,
                        'name' => $accessor->getValue($file, 'originalFilename'),
                        'mime_type' => $mimeType,
                        'icon' => $this->getFileIcon($iconClasses, $mimeType),
                        'object' => $file,
                    ];
                }
            }
        }
        $view->vars['files'] = $filesInfo;
    }

    private function translate($id, array $parameters = [])
    {
        return $this->translator->trans($id, $parameters, 'ArthemFileBundle');
    }

    private function getFileIcon(array $iconsClasses, $mimeType)
    {
        if (isset($iconsClasses[$mimeType])) {
            return $iconsClasses[$mimeType];
        }

        foreach ($iconsClasses as $mask => $iconsClass) {
            if (preg_match('#^'.$iconsClass.'$#', $mimeType)) {
                return $iconsClass;
            }
        }

        return null;
    }

    public function getBlockPrefix()
    {
        return 'arthem_file';
    }
}
