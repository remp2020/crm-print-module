<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\Application\Managers\ApplicationMountManager;
use Crm\PrintModule\Models\Config;
use Crm\PrintModule\Models\Export\FileSystem;
use Crm\UsersModule\Repositories\UserActionsLogRepository;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\CallbackResponse;
use Nette\DI\Attributes\Inject;

class ContentAdminPresenter extends AdminPresenter
{
    #[Inject]
    public ApplicationMountManager $mountManager;

    #[Inject]
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

        $path = $this->mountManager->getFilePath(FileSystem::DEFAULT_BUCKET_NAME, $file);
        $mimeType = $this->mountManager->mimeType($path);
        $fileSize = $this->mountManager->fileSize($path);

        $this->getHttpResponse()->setHeader('Content-Type', $mimeType);
        $this->getHttpResponse()->addHeader('Content-Disposition', "attachment; filename=" . $this->mountManager->getFileName($path));

        $response = new CallbackResponse(function () use ($path) {
            echo $this->mountManager->read($path);
        });
        $this->sendResponse($response);
    }
}
