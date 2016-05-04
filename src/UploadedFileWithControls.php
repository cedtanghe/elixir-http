<?php

namespace Elixir\HTTP;

use Elixir\Filter\FilterizableInterface;
use Elixir\Filter\FilterTrait;
use Elixir\STDLib\Facade\I18N;
use Elixir\STDLib\MessagesCatalog;
use Elixir\Validator\ValidatableInterface;
use Elixir\Validator\ValidateTrait;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class UploadedFileWithControls extends UploadedFile implements ValidatableInterface, FilterizableInterface
{
    use ValidateTrait;
    use FilterTrait;
    
    /**
     * @var string
     */
    const FILE_NOT_UPLOADED = 'file_not_uploaded';

    /**
     * @var string
     */
    const UPLOAD_ERROR = 'upload_error';

    /**
     * @var integer
     */
    const IDENTITY_NOT_FOUND = 4;
    
    /**
     * {@inheritdoc}
     */
    public function getDefaultCatalogMessages()
    {
        return [
            self::FILE_NOT_UPLOADED => I18N::__('The file is not uploaded.', ['context' => 'elixir']),
            self::UPLOAD_ERROR => I18N::__('An error occurred during upload.', ['context' => 'elixir'])
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($data = null, array $options = [])
    {
        if ($this->moved)
        {
            return true;
        }
        
        if (!$this->messagesCatalogue)
        {
            $this->setMessagesCatalog(MessagesCatalog::instance());
        }
        
        $this->validationErrors = [];
        
        switch($this->error)
        {
            case UPLOAD_ERR_OK:
                if(($this->file && $this->isUploaded()) || $this->stream)
                {
                    foreach ($this->validators as $config)
                    {
                        $validator = $config['validator'];
                        $o = $config['options'] + $options;
                        
                        $valid = $validator->validate($this, $o);
                        
                        if (!$valid)
                        {
                            $this->validationErrors = array_merge($this->validationErrors, $validator->getErrors());
                            
                            if ($this->breakChainValidationOnFailure)
                            {
                                break;
                            }
                        }
                    }
                }
                else
                {
                    if ($this->file)
                    {
                        $this->validationErrors = [$this->messagesCatalog->get(self::FILE_NOT_UPLOADED)];
                    }
                    else
                    {
                        $this->validationErrors = [$this->messagesCatalog->get(self::UPLOAD_ERROR)];
                    }
                }
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
            default:
                $this->validationErrors = [$this->messagesCatalog->get(self::UPLOAD_ERROR)];
        }
        
        return $this->hasError();
    }
    
    /**
     * {@inheritdoc}
     */
    public function filter($data = null, array $options = [])
    {
        foreach ($this->filters as $config)
        {
            $filter = $config['filter'];
            $o = $config['options'] + $options;
            
            $filter->filter($this, $o);
        }
        
        return true;
    }
}
