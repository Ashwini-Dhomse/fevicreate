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

        $user->setSchoolname(
            $dto->schoolname
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

        // Common Fields
        if ($dto->firstname !== null) {
            $user->setFirstname($dto->firstname);
        }

        if ($dto->lastname !== null) {
            $user->setLastname($dto->lastname);
        }

        if ($dto->gender !== null) {
            $user->setGender($dto->gender);
        }

        if ($dto->i_am !== null) {
            $user->setIAm($dto->i_am);
        }

        if ($dto->mobile !== null) {
            $user->setMobile($dto->mobile);
        }

        if ($dto->city !== null) {
            $user->setCity($dto->city);
        }

        if ($dto->state !== null) {
            $user->setState($dto->state);
        }

        if ($dto->schoolname !== null) {
            $user->setSchoolname($dto->schoolname);
        }

        if ($dto->email !== null) {
            $user->setEmail($dto->email);
        }

        if ($dto->child_one_fullname !== null) {
            $user->setChildOneFullname($dto->child_one_fullname);
        }

        if ($dto->child_one_dob !== null) {
            $user->setChildOneDob($dto->child_one_dob);
        }

        if ($dto->child_two_fullname !== null) {
            $user->setChildTwoFullname($dto->child_two_fullname);
        }

        if ($dto->child_two_dob !== null) {
            $user->setChildtwodob($dto->child_two_dob);
        }

        if ($dto->child_three_fullname !== null) {
            $user->setChildThreeFullname($dto->child_three_fullname);
        }

        if ($dto->child_three_dob !== null) {
            $user->setChildthreedob($dto->child_three_dob);
        }

        $user->save();

        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'schoolname' => $user->getSchoolName(),
        ];
    }

    /**
     * Get User By ID
     */
    public function getById(int $id): array
    {
        $user = Users::getById($id);

        if (!$user instanceof Users) {
            throw new \Exception('User not found');
        }
        $children = [];
        if (
            $user->getChildOneFullname() ||
            $user->getChildOneDob()
        ) {
            $children[] = [
                'childonefullname' => $user->getChildOneFullname(),
                'dob' => $user->getChildOneDob(),
            ];
        }

        if (
            $user->getChildTwoFullname() ||
            $user->getChildtwodob()
        ) {
            $children[] = [
                'childtwofullname' => $user->getChildTwoFullname(),
                'dob' => $user->getChildtwodob(),
            ];
        }

        if (
            $user->getChildThreeFullname() ||
            $user->getChildthreedob()
        ) {
            $children[] = [
                'childthreefullname' => $user->getChildThreeFullname(),
                'dob' => $user->getChildthreedob(),
            ];
        }

        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'gender' => $user->getGender(),
            'mobileNumber' => $user->getMobile(),
            'state' => $user->getState(),
            'city' => $user->getCity(),
            'email' => $user->getEmail(),
            'schoolName' => method_exists($user, 'getSchoolName')
                ? $user->getSchoolName()
                : null,
            'children' => $children,    
            'createdAt' => date(
                'Y-m-d H:i:s',
                $user->getCreationDate()
            ),
            'updatedAt' => date(
                'Y-m-d H:i:s',
                $user->getModificationDate()
            ),
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