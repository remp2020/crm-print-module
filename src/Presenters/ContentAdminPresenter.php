<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\Models\ApplicationMountManager;
use Crm\PrintModule\Export\FileSystem;
use Crm\PrintModule\Models\Config;
use Crm\UsersModule\Repository\UserActionsLogRepository;
use League\Flysystem\Adapter\AbstractAdapter;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;

class ContentAdminPresenter extends AdminPresenter
{
    /** @inject */
    public ApplicationMountManager $mountManager;

    /** @inject */
    public UserActionsLogRepository $userActionsLogRepository;

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

        if (!$this->mountManager->has($adapterPrefix . $file)) {
            throw new BadRequestException();
        }

        $this->userActionsLogRepository->add(
            userId: $this->getUser()->getId(),
            action: Config::USER_ACTION_PRINT_EXPORT_DOWNLOAD,
            params: ['file' => $file],
        );

        $filePath = $this->mountManager->getAdapter($adapterPrefix);
        if ($filePath instanceof AbstractAdapter) {
            $filePath = $filePath->applyPathPrefix($file);
        }

        $response = new FileResponse($filePath);
        // Nette appends Content-Range header even when no Range header is present, Varnish doesn't like that
        $response->resuming = false;
        $this->sendResponse($response);
    }
}
