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

    /**
     * @admin-access-level read
     */
    public function renderDefault()
    {
    }

    /**
     * @admin-access-level read
     */
    public function renderExportFile($file)
    {
        $adapterPrefix = FileSystem::DEFAULT_BUCKET_NAME . '://';

        if ($this->mountManager->has($adapterPrefix . $file)) {
            $filePath = $this->mountManager
                ->getAdapter($adapterPrefix)
                ->applyPathPrefix($file);

            $response = new FileResponse($filePath);
            // Nette appends Content-Range header even when no Range header is present, Varnish doesn't like that
            $response->resuming = false;
            $this->sendResponse($response);
        } else {
            throw new BadRequestException();
        }
    }
}
