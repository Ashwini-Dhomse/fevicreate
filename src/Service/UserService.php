<?php

namespace App\Service;

use App\DTO\UserDTO;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Users;

class UserService
{
    /**
     * Create User
     */
    public function create(UserDTO $dto): array
    {
        // =========================
        // Validate User Type
        // =========================
        $userType = strtolower(trim($dto->userType));

        if (!in_array($userType, ['parent', 'teacher', 'school'])) {
            throw new \Exception(
                'Invalid userType. Allowed: parent, teacher, school'
            );
        }

        // =========================
        // Duplicate Check
        // =========================
        $list = new Users\Listing();

        $list->setCondition('
            mobile = ? 
            OR teachermobile = ? 
            OR POCcontactnumber = ?
            OR email = ?
            OR teacheremail = ?
            OR principalemail = ?
        ', [
            $dto->mobile,
            $dto->mobile,
            $dto->mobile,
            $dto->email,
            $dto->email,
            $dto->email
        ]);

        $list->setLimit(1);

        if ($list->count() > 0) {
            throw new \Exception(
                'Mobile number or email already registered'
            );
        }

        // =========================
        // Parent Folder
        // =========================
        $parent = Folder::getByPath('/Users');

        if (!$parent) {

            $parent = new Folder();

            $parent->setKey('Users');
            $parent->setParentId(1);

            $parent->save();
        }

        // =========================
        // Create User Object
        // =========================
        $user = new Users();

        $user->setKey(
            \Pimcore\Model\Element\Service::getValidKey(
                $dto->mobile,
                'object'
            )
        );

        $user->setParentId($parent->getId());

        $user->setPublished(true);

        $user->setUserType($userType);

        // =========================
        // Parent User
        // =========================
        if ($userType === 'parent') {

            $user->setChildFirstName(
                $dto->childfirstname
            );

            $user->setChildLastName(
                $dto->childlastname
            );

            $user->setMobile(
                $dto->mobile
            );

            $user->setEmail(
                $dto->email
            );
        }

        // =========================
        // Teacher User
        // =========================
        elseif ($userType === 'teacher') {

            $user->setFirstName(
                $dto->firstname
            );

            $user->setLastName(
                $dto->lastname
            );

            $user->setTeachermobile(
                $dto->mobile
            );

            $user->setTeacheremail(
                $dto->email
            );
        }

        // =========================
        // School User
        // =========================
        elseif ($userType === 'school') {

            $user->setSchoolName(
                $dto->school_name
            );

            $user->setPOCcontactnumber(
                $dto->mobile
            );

            $user->setPrincipalemail(
                $dto->email
            );
        }

        // =========================
        // Password
        // =========================
        if (!empty($dto->password)) {

            $user->setPassword(
                password_hash(
                    $dto->password,
                    PASSWORD_BCRYPT
                )
            );
        }

        // =========================
        // Save
        // =========================
        $user->save();

        return [
            'userId' => $user->getId(),
            'userType' => $userType,
            'mobile' => $dto->mobile,
            'email' => $dto->email
        ];
    }

    /**
     * Login User
     */
    public function login(
        string $email,
        string $password
    ): Users {

        $list = new Users\Listing();

        $list->setCondition('
            email = ?
            OR teacheremail = ?
            OR principalemail = ?
        ', [
            $email,
            $email,
            $email
        ]);

        $list->setLimit(1);

        $users = $list->load();

        $user = $users[0] ?? null;

        if (!$user) {
            throw new \Exception('Invalid email');
        }

        if (
            !password_verify(
                $password,
                $user->getPassword()
            )
        ) {
            throw new \Exception('Invalid password');
        }

        return $user;
    }
}
