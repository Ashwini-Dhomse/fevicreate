<?php

namespace App\Controller;

use App\Library\EmailLibrary;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use symfony\Component\Routing\Annotation\Route;

class OtpApiController
{
    private const SMS_OTP_AUTHENTICATION_KEY = '5ULsYKUxA4J7EKN1fD2A9g==';

    private const MAX_FAILURE_ATTEMPT = 5;

    protected $apiResponse = [
        'status' => 200,
        'message' => 'OTP Sent successfully',
        'data' => []
    ];

    /**
     *
     * @Route("/api/otp/send")
     *
     *
     */
    public function sendOtpAction(Request $request): JsonResponse
    {

        $contentType = $request->headers->get('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($request->getContent(), true);

            // Check phone number is request parameter
            if (!isset($data['mobile'])) {
                $this->apiResponse['status'] = 400;
                $this->apiResponse['message'] = 'Phone number missing';

                return $this->sendApiResponse();
            }

            // Get employee object by phone number
            $phoneNumber = $data['mobile'];
            $employee = [];

            /**
             * 1️⃣ Check Parent (mobile)
             */
            $parentList = new DataObject\Users\Listing();
            $parentList->setCondition('mobile = ?', [$phoneNumber]);
            $parentList->setLimit(1);
            $parent = $parentList->getData();

            if (!empty($parent)) {
                $employee = $parent[0];
            }

            /**
             * 2️⃣ If not Parent → Check Teacher (teachermobile)
             */
            if (!$employee) {
                $teacherList = new DataObject\Users\Listing();
                $teacherList->setCondition('teachermobile = ?', [$phoneNumber]);
                $teacherList->setLimit(1);
                $teacher = $teacherList->getData();

                if (!empty($teacher)) {
                    $employee = $teacher[0];
                }
            }

            /**
             * 3️⃣ If not Teacher → Check School (POCcontactnumber)
             */
            if (!$employee) {
                $schoolList = new DataObject\Users\Listing();
                $schoolList->setCondition('POCcontactnumber = ?', [$phoneNumber]);
                $schoolList->setLimit(1);
                $school = $schoolList->getData();

                if (!empty($school)) {
                    $employee = $school[0];
                }
            }

            /**
             * 4️⃣ If not found in all three
             */
            if (!$employee) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'This number is not a registered user. Please edit the number or try logging in with a new one.';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber
                ];

                return $this->sendApiResponse();
            }

            //$employee = reset($employees);
            // Send OTP to customer
            $response = $this->sendOtp($phoneNumber, $employee->getId());
            if (!$response) {
                $this->apiResponse['status'] = 400;
                $this->apiResponse['message'] = 'Error while sending OTP.';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber
                ];
            }

            return $this->sendApiResponse();
        }
    }

    /**
     * @Route("/api/register", methods={"POST"})
     */
    public function registerAction(Request $request): JsonResponse
    {
        $contentType = $request->headers->get('Content-Type');

        if (strpos($contentType, 'application/json') === false) {
            $this->apiResponse['status'] = 400;
            $this->apiResponse['message'] = 'Content-Type must be application/json';

            return $this->sendApiResponse();
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $this->apiResponse['status'] = 400;
            $this->apiResponse['message'] = 'Invalid JSON';

            return $this->sendApiResponse();
        }

        // Required Fields
        if (empty($data['userType']) || empty($data['email']) || empty($data['mobile'])) {
            $this->apiResponse['status'] = 400;
            $this->apiResponse['message'] = 'userType, email, mobile are required';

            return $this->sendApiResponse();
        }

        $userType = strtolower(trim($data['userType']));
        $mobile   = trim($data['mobile']);
        // $name     = trim($data['name']);
        $email    = $data['email'] ?? null;

        if (!in_array($userType, ['parent', 'teacher', 'school'])) {
            $this->apiResponse['status'] = 400;
            $this->apiResponse['message'] = 'Invalid userType. Allowed: parent, teacher, school';

            return $this->sendApiResponse();
        }

        // Check if mobile exists in any field
        $list = new DataObject\Users\Listing();
        $list->setCondition('
			mobile = ? OR teachermobile = ? OR POCcontactnumber = ? 
			OR email = ? OR teacheremail = ? OR principalemail = ?
		', [
            $mobile, $mobile, $mobile,
            $email, $email, $email
        ]);
        $list->setLimit(1);
        if ($list->count() > 0) {
            $this->apiResponse['status'] = 409;
            $this->apiResponse['message'] = 'Mobile number or email already registered';

            return $this->sendApiResponse();
        }

        // Create User
        $user = new DataObject\Users();
        $user->setKey(\Pimcore\Model\Element\Service::getValidKey($mobile, 'object'));
        $user->setParentId(2);
        $user->setUserType($userType);
        //$user->setEmail($email);

        if ($userType === 'parent') {
            $user->setChildFirstName($data['childfirstname'] ?? '');
            $user->setChildLastName($data['childlastname'] ?? '');
            $user->setMobile($mobile);
            $user->setEmail($email);
        } elseif ($userType === 'teacher') {
            $user->setFirstName($data['firstname'] ?? '');
            $user->setLastName($data['lastname'] ?? '');
            $user->setTeachermobile($mobile);
            $user->setTeacheremail($email);
        } elseif ($userType === 'school') {
            $user->setSchoolName($data['school_name'] ?? '');
            $user->setPOCcontactnumber($mobile);
            $user->setPrincipalemail($email);
        } else {
            $this->apiResponse['status'] = 400;
            $this->apiResponse['message'] = 'Invalid user type';

            return $this->sendApiResponse();
        }

        $user->setPublished(true);

        try {
            $user->save();
        } catch (\Exception $e) {
            $this->apiResponse['status'] = 500;
            $this->apiResponse['message'] = 'Registration failed';

            return $this->sendApiResponse();
        }

        $this->apiResponse['message'] = 'Registration successful';
        $this->apiResponse['data'] = [
            'userId' => $user->getId(),
            'userType' => $userType,
            'mobile' => $mobile
        ];

        return $this->sendApiResponse();
    }

    /**
     *
     * @Route("/api/otp/validate")
     *
     *
     */
    public function validateOtpAction(Request $request): JsonResponse
    {

        $contentType = $request->headers->get('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($request->getContent(), true);

            // Check mobile number is request parameter
            if (!isset($data['mobile']) || !isset($data['otp'])) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'Mobile number or OTP is missing';

                return $this->sendApiResponse();
            }

            // Get employee object by phone number
            $phoneNumber = $data['mobile'];
            $employee = [];

            /**
             * 1️⃣ Check Parent (mobile)
             */
            $parentList = new DataObject\Users\Listing();
            $parentList->setCondition('mobile = ?', [$phoneNumber]);
            $parentList->setLimit(1);
            $parent = $parentList->getData();

            if (!empty($parent)) {
                $employee = $parent[0];
            }

            /**
             * 2️⃣ If not Parent → Check Teacher (teachermobile)
             */
            if (!$employee) {
                $teacherList = new DataObject\Users\Listing();
                $teacherList->setCondition('teachermobile = ?', [$phoneNumber]);
                $teacherList->setLimit(1);
                $teacher = $teacherList->getData();

                if (!empty($teacher)) {
                    $employee = $teacher[0];
                }
            }

            /**
             * 3️⃣ If not Teacher → Check School (POCcontactnumber)
             */
            if (!$employee) {
                $schoolList = new DataObject\Users\Listing();
                $schoolList->setCondition('POCcontactnumber = ?', [$phoneNumber]);
                $schoolList->setLimit(1);
                $school = $schoolList->getData();

                if (!empty($school)) {
                    $employee = $school[0];
                }
            }

            // Employee not found with given phone number
            if (!$employee) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'Employee not found with given mobile number';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber
                ];

                return $this->sendApiResponse();
            }

            //$employee = reset($employees);

            //Create customer token and pass in response
            $db = Db::getConnection();
            // Check OTP is exist for given phone number
            $userData = $this->getEmployeeOtpByPhoneNumber($phoneNumber);
            if (empty($userData)) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'OTP not generated, please generate OTP and try again';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber
                ];

                return $this->sendApiResponse();
            }

            // Check OTP max failure attempts
            if ($userData['failure_attempt'] >= self::MAX_FAILURE_ATTEMPT) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'Max failure attempt is 5';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber
                ];

                return $this->sendApiResponse();
            }

            // Check OTP given is same as stored for phone number
            $otp = $data['otp'];
            if ($userData['otp'] != $otp) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'OTP is not valid';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber,
                    'failure_attempt' => $userData['failure_attempt'] + 1
                ];

                $this->updateFailureAttempt($userData['id'], $userData['failure_attempt'] + 1);

                return $this->sendApiResponse();
            }

            // Check OTP is expired or active
            if (!$this->isOtpValid($userData['expires_at'])) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'OTP is expired';
                $this->apiResponse['data'] = [
                    'phonenumber' => $phoneNumber
                ];

                return $this->sendApiResponse();
            }

            $currentDateTime = new \DateTime();
            $currentDateTimeFormated = $currentDateTime->format('Y-m-d H:i:s');
            $expiryTime = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($currentDateTimeFormated)));

            $generatedToken = $this->getRandomString(50);

            $query = 'SELECT * FROM customer_token WHERE id = :userId';
            $params = ['userId' =>  $userData['id']];

            $result = $db->fetchOne($query, $params);
            if ($result > 0) {
                $query = 'UPDATE customer_token SET token = :token, created_at = :created_at,  expires_at = :expires_at WHERE id = :id';

                $params = [
                    'id' => $result,
                    'token' => $generatedToken,
                    'created_at' => $currentDateTimeFormated,
                    'expires_at' => $expiryTime
                ];

                $db->executeQuery($query, $params);

            } else {

                $insertQuery = 'INSERT INTO customer_token (id, token, created_at, expires_at) VALUES (:userId, :token, :created_at, :expires_at)';

                $params = [
                    'userId' => $userData['id'],
                    'token' => $generatedToken,
                    'created_at' => $currentDateTimeFormated,
                    'expires_at' => $expiryTime
                ];

                $db->executeQuery($insertQuery, $params);
            }

            $brandLabel = 'fevicol';

            /*$salesGroupCode = trim($employee->getsalesGroupCode());

            if(!empty($salesGroupCode)) {

                $divisionData = DataObject\SalesDivisionMaster::getBysalesGroupCode($salesGroupCode);

                foreach ($divisionData as $i => $divisionInfo) {
                    $brandLabel = $divisionInfo->gethamburgerMenuCode();
                }
            }*/

            $this->deleteOtp($userData['id']);
            $this->apiResponse['message'] = 'OTP Validated successfully';
            $this->apiResponse['data'] = [
                'customertoken' => $generatedToken,
                'userType' => $employee->getuserType()
            ];

            return $this->sendApiResponse();
        }
    }

    private function getUserProfileUrl($employee)
    {
        $image = $employee->get('profilePhoto');
        $imageUrl = '';
        if ($image && $image instanceof Asset\Image) {
            $imageUrl = $image->getFullPath() . $_ENV['SASTOKEN'];
        }

        return $imageUrl;
    }

    /**
     *
     * @Route("/api/customer/logout")
     *
     *
     */
    public function customerLogoutAction(Request $request): JsonResponse
    {
        $contentType = $request->headers->get('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {

            $data = json_decode($request->getContent(), true);

            $this->apiResponse['logout'] = true;
            // Check custoemr token is request parameter
            if (!isset($data['customertoken']) || $data['customertoken'] == '') {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'Customer token is missing';

                return $this->sendApiResponse();
            }

            $customerToken = $data['customertoken'];

            // Check token is exist or not
            $userId = $this->getEmployeeByToken($customerToken);
            if ($userId == 0) {
                $this->apiResponse['status'] = 404;
                $this->apiResponse['message'] = 'Token is not valid';
                $this->apiResponse['data'] = [
                    'customertoken' => $customerToken
                ];

                return $this->sendApiResponse();
            }

            // Delete customer token to logout
            if (!$this->deleteCustomerToken($userId)) {
                $this->apiResponse['message'] = 'There is some issue while logout';

                return $this->sendApiResponse();
            }

            $this->apiResponse['message'] = 'Customer logged out successfully';

            return $this->sendApiResponse();
        }
    }

    /**
     * Send Login OTP
     */
    private function sendOtp($phoneNumber, $id)
    {
        // Generate OTP
        $otp = $this->generateOtp($phoneNumber, $id);
        if (!$otp) {
            return false;
        }

        $key = self::SMS_OTP_AUTHENTICATION_KEY;

        $template = urlencode('Dear User, '.$otp.' is OTP to login Jharokha app. Do not share OTP with anyone. Regards, Pidilite');
        $sender = 'FCCRTR';
        $url = "http://japi.instaalerts.zone/httpapi/QueryStringReceiver?ver=1.0&key={$key}&encrpt=0&dest={$phoneNumber}&send={$sender}&text={$template}";

        // CURL Request to send OTP
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout for the entire request
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout for the connection

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                curl_close($ch);

                return false;
            } else {
                curl_close($ch);

                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate OTP
     * Save to customer_otp table
     */
    private function generateOtp($phoneNumber, $userId)
    {
        $generatedOtp = rand(0, 9999);
        $otp = str_pad($generatedOtp, 4, '0', STR_PAD_LEFT);

        // OTP expiry is 10 minutes
        $currentDateTime = new \DateTime();
        $currentDateTimeFormated = $currentDateTime->format('Y-m-d H:i:s');
        $expiryTime = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($currentDateTimeFormated)));

        // SQL query for upsert
        $sql = '
            INSERT INTO customer_otp (id, phonenumber, otp, failure_attempt, created_at, expires_at)
            VALUES (:id, :phonenumber, :otp, :failure_attempt, :created_at, :expires_at)
            ON DUPLICATE KEY UPDATE
                otp = VALUES(otp),
                failure_attempt = VALUES(failure_attempt),
                created_at = VALUES(created_at),
                expires_at = VALUES(expires_at);
        ';

        try {
            $db = Db::get();
            $db->executeStatement($sql, [
                'id' => $userId,
                'phonenumber' => $phoneNumber,
                'otp' => $otp,
                'failure_attempt' => 0,
                'created_at' => $currentDateTimeFormated,
                'expires_at' => $expiryTime,
            ]);

            return $otp;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getEmployeeOtpByPhoneNumber($phoneNumber)
    {
        $db = Db::get();

        $sql = "SELECT * FROM `customer_otp` WHERE phonenumber = '$phoneNumber'";
        $userData = $db->fetchAssociative($sql);

        if (isset($userData['id'])) {
            return $userData;
        }

        return [];
    }

    /**
     * Get customer token record by token
     */
    private function getEmployeeByToken($token)
    {
        $db = Db::get();

        $sql = "SELECT id FROM `customer_token` WHERE token = '$token'";
        $userId = $db->fetchOne($sql);

        return ($userId > 0) ? $userId : 0;
    }

    /**
     * Check OPT is valid or expired
     */
    public function isOtpValid($expiresAt)
    {
        $expiryDate = $expiresAt;
        $currentDateTime = new \DateTime();
        $now = $currentDateTime->format('Y-m-d H:i:s');
        if (strtotime($now) <= strtotime($expiryDate)) {
            return true;
        }

        return false;
    }

    /**
     * Update failure attempt in customer_otp table
     */
    private function updateFailureAttempt($id, $failureAttempt)
    {
        $db = Db::get();

        $sql = 'UPDATE customer_otp SET failure_attempt = :failure_attempt WHERE id = :id';

        try {
            $db->executeStatement($sql, [
                'failure_attempt' => $failureAttempt,
                'id' => $id
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete OTP
     */
    public function deleteOtp($id)
    {
        $db = Db::get();

        $sql = 'DELETE FROM customer_otp WHERE id = :id';
        try {
            $db->executeStatement($sql, [
                'id' => $id
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete OTP
     */
    public function deleteCustomerToken($id)
    {
        $db = Db::get();

        $sql = 'DELETE FROM customer_token WHERE id = :id';
        try {
            $db->executeStatement($sql, [
                'id' => $id
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate random string for token
     */
    public function getRandomString($n)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = random_int(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    /**
     * Send response
     */
    private function sendApiResponse()
    {

        $response = new JsonResponse($this->apiResponse);

        // Add Referrer-Policy header here
        //$response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        // $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none''");
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Optional: Clear-Site-Data (e.g. on logout APIs)
        if (!empty($this->apiResponse['logout'])) {
            $response->headers->set('Clear-Site-Data', '"cache", "cookies", "storage", "executionContexts"');
        }

        $baseUrl = \Pimcore\Tool::getHostUrl();

        try {

            $response->headers->set('X-Powered-By', '');
            $response->headers->remove('X-Pimcore-Output-Cache');
            $response->headers->remove('X-Generator');
            ini_set('expose_php', 'off');
        } catch (\Throwable $th) {
        }

        // Secure CORS headers (adjust as needed)
        $response->headers->set('Access-Control-Allow-Origin', $baseUrl);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        $response->headers->set('Server', 'MySecureAPI');

        return $response;
    }

    /**
     *
     * @Route("/api/testemail")
     *
     *
     */
    public function testEmailAction(Request $request, EmailLibrary $emailLib): JsonResponse
    {

        $emailData = [
        'to' => 'ashwini.dhomse@embitel.com',
        'name' => 'Ashwini',
        'email' => 'ashwini.dhomse@embitel.com',
    ];

        try {
            $emailLib->sendRegistrationEmail($emailData);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Email sent successfully'
            ]);
        } catch (TransportExceptionInterface $e) {

            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
