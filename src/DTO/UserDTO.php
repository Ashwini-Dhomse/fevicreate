<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

class UserDTO
{
    public ?string $firstname = null;
    public ?string $lastname = null;
    public ?string $gender = null;
    public ?string $i_am = null;
    public ?string $mobile = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $schoolname = null;
    public ?string $email = null;

    public ?string $child_one_fullname = null;
    public ?string $child_one_dob = null;

    public ?string $child_two_fullname = null;
    public ?string $child_two_dob = null;

    public ?string $child_three_fullname = null;
    public ?string $child_three_dob = null;

    /**
     * Create DTO from Request
     */
    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);

        $dto = new self();

        $dto->firstname = $data['firstname'] ?? null;
        $dto->lastname = $data['lastname'] ?? null;
        $dto->gender = $data['gender'] ?? null;
        $dto->i_am = $data['i_am'] ?? null;
        $dto->mobile = $data['mobile'] ?? null;
        $dto->city = $data['city'] ?? null;
        $dto->state = $data['state'] ?? null;
        $dto->schoolname = $data['schoolname'] ?? null;
        $dto->email = $data['email'] ?? null;

        $dto->child_one_fullname = $data['child_one_fullname'] ?? null;
        $dto->child_one_dob = $data['child_one_dob'] ?? null;

        $dto->child_two_fullname = $data['child_two_fullname'] ?? null;
        $dto->child_two_dob = $data['child_two_dob'] ?? null;

        $dto->child_three_fullname = $data['child_three_fullname'] ?? null;
        $dto->child_three_dob = $data['child_three_dob'] ?? null;

        return $dto;
    }

    /**
     * Validate Request Data
     */
    public function validate(): array
    {
        $errors = [];

        // Required Fields
        if (empty($this->firstname)) {
            $errors[] = 'firstname is required';
        }

        if (empty($this->lastname)) {
            $errors[] = 'lastname is required';
        }

        if (empty($this->mobile)) {
            $errors[] = 'mobile is required';
        }

        if (empty($this->city)) {
            $errors[] = 'city is required';
        }

        if (empty($this->state)) {
            $errors[] = 'state is required';
        }

        if (
            empty($this->email) ||
            !filter_var($this->email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Valid email is required';
        }

        // Gender Validation
        if (
            !empty($this->gender) &&
            !in_array(strtolower($this->gender), ['male', 'female'])
        ) {
            $errors[] = 'Invalid gender. Allowed: male, female';
        }

        // I Am Validation
        if (
            !empty($this->i_am) &&
            !in_array(strtolower($this->i_am), ['parent', 'teacher'])
        ) {
            $errors[] = 'Invalid i_am. Allowed: parent, teacher';
        }

        // Parent Validation
        if (strtolower($this->i_am ?? '') === 'parent') {

            if (empty($this->child_one_fullname)) {
                $errors[] = 'child_one_fullname is required';
            }

            if (empty($this->child_one_dob)) {
                $errors[] = 'child_one_dob is required';
            }
        }

        return $errors;
    }
}