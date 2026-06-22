<?php

namespace App\Service;

use App\DTO\UserDTO;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Users;
use Pimcore\Model\Element\Service as ElementService;

class UserService
{
    /**
     * Create User
     */
    public function create(UserDTO $dto): array
    {
        // Validate DTO
        $errors = $dto->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        $userType = strtolower(trim($dto->i_am));

        // Validate user type
        if (!in_array($userType, ['parent', 'teacher'])) {
            throw new \Exception(
                'Invalid i_am. Allowed: parent, teacher'
            );
        }

        // Check duplicate email/mobile
        $list = new Users\Listing();

        $list->setCondition(
            'mobile = ? OR email = ?',
            [
                $dto->mobile,
                $dto->email
            ]
        );

        $list->setLimit(1);

        if ($list->count() > 0) {
            throw new \Exception(
                'Mobile number or email already registered'
            );
        }

        // Create parent folder if not exists
        $parent = Folder::getByPath('/Users');

        if (!$parent) {

            $parent = new Folder();
            $parent->setKey('Users');
            $parent->setParentId(1);
            $parent->save();
        }

        // Create object
        $user = new Users();

        $user->setKey(
            ElementService::getValidKey(
                $dto->mobile,
                'object'
            )
        );

        $user->setParentId(
            $parent->getId()
        );

        $user->setPublished(true);

        // Common Fields
        $user->setFirstname(
            $dto->firstname
        );

        $user->setLastname(
            $dto->lastname
        );

        $user->setGender(
            $dto->gender
        );

        $user->setIAm(
            $dto->i_am
        );

        $user->setMobile(
            $dto->mobile
        );

        $user->setCity(
            $dto->city
        );

        $user->setState(
            $dto->state
        );

        $user->setSchoolName(
            $dto->school_name
        );

        $user->setEmail(
            $dto->email
        );

        // Child 1
        $user->setChildOneFullname(
            $dto->child_one_fullname
        );

        $user->setChildOneDob(
            $dto->child_one_dob
        );

        // Child 2
        $user->setChildTwoFullname(
            $dto->child_two_fullname
        );

        $user->setChildtwodob(
            $dto->child_two_dob
        );

        // Child 3
        $user->setChildThreeFullname(
            $dto->child_three_fullname
        );

        $user->setChildthreedob(
            $dto->child_three_dob
        );

        $user->save();

        return [
            'success' => true,
            'userId' => $user->getId(),
            'firstname' => $dto->firstname,
            'lastname' => $dto->lastname,
            'email' => $dto->email,
            'mobile' => $dto->mobile,
            'i_am' => $dto->i_am
        ];
    }

    public function update(
    int $id,
    UserDTO $dto
    ): array {

        $user = Users::getById($id);

        if (!$user instanceof Users) {
            throw new \Exception('User not found');
        }

        $user->setFirstname($dto->firstname);
        $user->setLastname($dto->lastname);
        $user->setEmail($dto->email);
        $user->setMobile($dto->mobile);
        $user->setGender($dto->gender);
        $user->setState($dto->state);
        $user->setCity($dto->city);

        // Optional fields
        if (!empty($dto->schoolName)) {
            $user->setSchoolName($dto->schoolName);
        }

        if (!empty($dto->childFirstName)) {
            $user->setChildfirstname($dto->childFirstName);
        }

        if (!empty($dto->childLastName)) {
            $user->setChildlastname($dto->childLastName);
        }

        // Update password only if provided
        if (!empty($dto->password)) {
            $user->setPassword(
                password_hash(
                    $dto->password,
                    PASSWORD_BCRYPT
                )
            );
        }

        $user->save();

        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * Login User
     */
    public function login(
        string $email
    ): Users {

        $list = new Users\Listing();

        $list->setCondition(
            'email = ?',
            [$email]
        );

        $list->setLimit(1);

        $users = $list->load();

        $user = $users[0] ?? null;

        if (!$user) {
            throw new \Exception(
                'User not found'
            );
        }

        return $user;
    }
}