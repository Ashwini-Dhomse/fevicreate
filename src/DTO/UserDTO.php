<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

class UserDTO
{
    public ?string $userType = null;
    public ?string $mobile = null;
    public ?string $email = null;
    public ?string $password = null;

    // Parent
    public ?string $childfirstname = null;
    public ?string $childlastname = null;

    // Teacher
    public ?string $firstname = null;
    public ?string $lastname = null;

    // School
    public ?string $school_name = null;

    /**
     * Create DTO from Request
     */
    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);

        $dto = new self();

        $dto->userType = $data['userType'] ?? null;
        $dto->mobile = $data['mobile'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->password = $data['password'] ?? null;

        // Parent
        $dto->childfirstname = $data['childfirstname'] ?? null;
        $dto->childlastname = $data['childlastname'] ?? null;

        // Teacher
        $dto->firstname = $data['firstname'] ?? null;
        $dto->lastname = $data['lastname'] ?? null;

        // School
        $dto->school_name = $data['school_name'] ?? null;

        return $dto;
    }

    /**
     * Validate Request Data
     */
    public function validate(): array
    {
        $errors = [];

        // =========================
        // Common Validation
        // =========================
        if (empty($this->userType)) {
            $errors[] = 'userType is required';
        }

        if (empty($this->mobile)) {
            $errors[] = 'mobile is required';
        }

        if (
            empty($this->email) ||
            !filter_var($this->email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Valid email is required';
        }

        // =========================
        // User Type Validation
        // =========================
        if (
            !empty($this->userType) &&
            !in_array(
                strtolower($this->userType),
                ['parent', 'teacher', 'school']
            )
        ) {
            $errors[] = 'Invalid userType. Allowed: parent, teacher, school';
        }

        // =========================
        // Parent Validation
        // =========================
        if (strtolower($this->userType ?? '') === 'parent') {

            if (empty($this->childfirstname)) {
                $errors[] = 'childfirstname is required';
            }

            if (empty($this->childlastname)) {
                $errors[] = 'childlastname is required';
            }
        }

        // =========================
        // Teacher Validation
        // =========================
        if (strtolower($this->userType ?? '') === 'teacher') {

            if (empty($this->firstname)) {
                $errors[] = 'firstname is required';
            }

            if (empty($this->lastname)) {
                $errors[] = 'lastname is required';
            }
        }

        // =========================
        // School Validation
        // =========================
        if (strtolower($this->userType ?? '') === 'school') {

            if (empty($this->school_name)) {
                $errors[] = 'school_name is required';
            }
        }

        return $errors;
    }
}