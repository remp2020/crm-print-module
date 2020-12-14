<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\Models\ApplicationMountManager;
use Crm\PrintModule\Export\FileSystem;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;

class ContentAdminPresenter extends AdminPresenter
{
    /** @var ApplicationMountManager @inject */
    public $mountManager;

    public function renderDefault()
    {
    }

    public function renderExportFile($file)
    {
        $adapterPrefix = FileSystem::DEFAULT_BUCKET_NAME . '://';

        if ($this->mountManager->has($adapterPrefix . $file)) {
            $filePath = $this->mountManager
                ->getAdapter($adapterPrefix)
                ->applyPathPrefix($file);

            $this->sendResponse(new FileResponse($filePath));
        } else {
            throw new BadRequestException();
        }
    }
}
