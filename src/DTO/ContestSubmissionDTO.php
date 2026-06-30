<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ContestSubmissionDTO
{
    public string $childName;
    public string $parentEmail;
    public ?string $parentPhone = null;
    public ?string $artworkTitle = null;
    public array $documents = [];

    public function __construct(array $data)
    {
        $this->userId        = isset($data['userId']) ? (int) $data['userId'] : null;
        $this->childName     = $data['childName'] ?? '';
        $this->parentEmail   = $data['parentEmail'] ?? '';
        $this->parentPhone   = $data['parentPhone'] ?? null;
        $this->artworkTitle  = $data['artworkTitle'] ?? null;
        $this->documents     = $data['documents'] ?? [];
    }

    public static function fromRequest(Request $request): self
    {
        $data = $request->request->all();
        $data['documents'] = $request->files->get('documents', []);

        if (!is_array($data['documents'])) {
            $data['documents'] = [$data['documents']];
        }

        return new self($data);
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->userId)) {
            $errors[] = 'User ID is required';
        }
        
        if (empty($this->childName)) {
            $errors[] = 'Child name is required';
        }

        if (empty($this->parentEmail) || !filter_var($this->parentEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }

        if (empty($this->parentPhone)) {
            $errors[] = 'Phone number is required';
        }

        if (empty($this->artworkTitle)) {
            $errors[] = 'Artwork title is required';
        }

        if (empty($this->documents)) {
            $errors[] = 'At least one document is required';
        }

        foreach ($this->documents as $file) {

            if (!$file instanceof UploadedFile) {
                $errors[] = 'Invalid file upload';
                continue;
            }

            if ($file->getSize() > 3 * 1024 * 1024) {
                $errors[] = 'File size must be less than 3MB';
            }

            $mime = $file->getClientMimeType();

            if (!in_array($mime, [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ])) {
                $errors[] = "Invalid file type ($mime)";
            }
        }

        return $errors;
    }
}
