<?php

namespace Arthem\Bundle\FileBundle\Validator;

use Arthem\Bundle\FileBundle\Model\FileInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator as BaseFileValidator;

class FileValidator extends BaseFileValidator
{
    /**
     * @param mixed           $value
     * @param File|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        if ($constraint->multiple) {
            foreach ($value as $file) {
                if ($file instanceof FileInterface) {
                    $this->validateFile($file, $constraint);
                }
            }
        }
        if ($value instanceof FileInterface) {
            $this->validateFile($value, $constraint);
        }
    }

    protected function validateFile(FileInterface $file, $constraint)
    {
        parent::validate($file, $constraint);
    }
}
