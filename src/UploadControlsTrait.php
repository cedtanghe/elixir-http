<?php

namespace Elixir\HTTP;

use Elixir\Filter\FilterTrait;
use Elixir\STDLib\MessagesCatalog;
use Elixir\Validator\ValidateTrait;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

trait UploadControlsTrait 
{
    use ValidateTrait;
    use FilterTrait;
    
    /**
     * @see ValidateTrait::getDefaultCatalogMessages()
     */
    public function getDefaultCatalogMessages()
    {
        return [
            'file_not_uploaded' => 'The file is not uploaded.',
            'upload_error' => 'An error occurred during upload.'
        ];
    }
    
    /**
     * @see ValidateTrait::validate()
     */
    public function validate()
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
                        $options = $config['options'];
                        
                        $valid = $validator->validate($this, $options);
                        
                        if (!$valid)
                        {
                            $this->validationErrors = array_merge($this->validationErrors, $validator->getErrors());
                            
                            if ($this->validationErrorBreak)
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
                        $this->validationErrors = [$this->messagesCatalogue->get('file_not_uploaded')];
                    }
                    else
                    {
                        $this->validationErrors = [$this->messagesCatalogue->get('upload_error')];
                    }
                }
            break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
            default:
                $this->validationErrors = [$this->messagesCatalogue->get('upload_error')];
            break;
        }
        
        return $this->hasValidationError();
    }
    
    /**
     * @see FilterTrait::filter()
     */
    public function filter()
    {
        foreach ($this->filters as $config)
        {
            $filter = $config['filter'];
            $options = $config['options'];

            $filter->filter(&$this, $options);
        }
        
        return true;
    }
}
