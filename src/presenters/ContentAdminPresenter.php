<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Nette\Application\BadRequestException;

use Nette\Application\Responses\FileResponse;

class ContentAdminPresenter extends AdminPresenter
{
    public function renderDefault()
    {
    }

    public function renderExportFile($file)
    {
        if (file_exists(APP_ROOT . 'content/export/' . $file)) {
            $this->sendResponse(new FileResponse(APP_ROOT . 'content/export/' . $file));
        } else {
            throw new BadRequestException();
        }
    }
}
