<?php

namespace App\Service;

use App\DTO\ContestSubmissionDTO;
use App\Repository\ContestSubmissionRepository;
use App\Transformer\ContestSubmissionTransformer;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ContestSubmission;
use Pimcore\Model\DataObject\Users;

class ContestSubmissionService
{
    private $repository;
    private $fileService;

    public function __construct(
        FileUploadService $fileService,
        ContestSubmissionRepository $repository,
    ) {
        $this->fileService = $fileService;
        $this->repository = $repository;
    }

    public function save(ContestSubmissionDTO $dto)
    {
         $user = Users::getById($dto->userId);

        if (!$user instanceof Users) {
            throw new \Exception('User not found.');
        }
        
        $object = new ContestSubmission();

        $date = date('M Y');

        $contentDirectory = DataObject\Service::createFolderByPath('/Contest Submissions/'. $date .'/');
        $contentDirectoryId = $contentDirectory->getId();

        $objectKey = 'contest_'.$dto->artworkTitle.'_'.rand(1, 999);
        $object->setKey($objectKey);
        $object->setParentId($contentDirectoryId);
$user = Users::getById($dto->userId);

if (!$user instanceof Users) {
    throw new \Exception('User not found');
}
        $object->setUser(DataObject\Users::getById($user->getId()));
        $object->setChildName($dto->childName);
        $object->setParentEmail($dto->parentEmail);
        $object->setParentPhone($dto->parentPhone);
        $object->setArtworkTitle($dto->artworkTitle);
        $object->setDescription($dto->description);

        // Upload files
        $assets = [];

        $year = date('Y');
        $month = date('m');

        $folderPath = "/contest-submissions/$year/$month";

        foreach ($dto->documents as $file) {
            $assets[] = $this->fileService->upload($file, $folderPath, 'contest_');
        }

        $object->setDocuments($assets);
        $object->setPublished(true);
        $object->save();

        return $object->getId();
    }

    public function getList($page = 1, $limit = 10)
    {

        $list = $this->repository->getList($page, $limit);

        $data = [];

        foreach ($list as $item) {
            $data[] = ContestSubmissionTransformer::list($item);
        }

        return $data;
    }

    public function getDetail($id)
    {
        $item = $this->repository->getById($id);

        if (!$item) {
            throw new \Exception('Not found');
        }

        return ContestSubmissionTransformer::detail($item);
    }

    /**
     * Get contest submission list for a specific user.
     *
     * @param int $userId User Object ID
     * @param int $page   Current page number
     * @param int $limit  Number of records per page
     *
     * @return array
     *
     * @throws \Exception When user does not exist.
     */
    public function getByUser(int $userId, int $page = 1, int $limit = 10): array
    {
        // Verify user exists
        $user = Users::getById($userId);

        if (!$user instanceof Users) {
            throw new \Exception('User not found.');
        }

        // Fetch contest submissions
        $list = $this->repository->getByUser($userId, $page, $limit);

        $data = [];

        // Transform objects into API response
        foreach ($list as $item) {
            $data[] = ContestSubmissionTransformer::list($item);
        }

        return [
            'userId' => $userId,
            'total'  => $list->getTotalCount(),
            'page'   => $page,
            'limit'  => $limit,
            'data'   => $data,
        ];
    }
}
