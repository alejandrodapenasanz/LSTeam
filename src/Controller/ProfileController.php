<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use DateTime;
use Exception;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;

final class ProfileController
{

    private const UPLOADS_DIR = __DIR__ . '/../../public/uploads';

    private const UNEXPECTED_ERROR = "[ERROR] An unexpected error occurred uploading the file '%s'";

    private const INVALID_EXTENSION_ERROR = "[ERROR] The received file extension '%s' is not jpg or png";

    private const INVALID_SIZE_ERROR = "[ERROR] The received file size '%s' is greater than 1MB";

    private const INVALID_DIM_ERROR = "[ERROR] The received file dimensions '%s' are greater than 500x500";

    // We use this const to define the extensions that we are going to allow
    private const ALLOWED_EXTENSIONS = ['jpg', 'png'];

    private Twig $twig;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function showProfileAction(Request $request, Response $response): Response
    {
        
        $picture = $this->showPicture();

        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);

            //Checking phone
            $phone = '';
            if(!empty($user->phone())) $phone = 'value=' . $user->phone();

            return $this->twig->render(
                $response,
                'profile.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'username' => $user->username(),
                    'email' => $user->email(),
                    'birthday' => $user->birthday(),
                    'phone' => $phone,
                    'picture' => $picture,
                ]);

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'login.twig',
                [
                   'sessionStatus' => $_SESSION['logged'],
                   'information' => $information,
                   'picture' => $picture,
                ]
            );

        }

    }

    public function uploadFileAction(Request $request, Response $response): Response
    {
        
        $comprobation = 0;
        $information = [];
        $uuid = Uuid::uuid4();

        $user = $this->userRepository->getUser($_SESSION['id']);

        $uploadedFiles = $request->getUploadedFiles();
        $data = $request->getParsedBody();

        //Checking phone number
        if(!empty($data['phone'])) {

            if (strlen($data['phone']) == 12) {

                if (preg_match('[^\+34]', $data['phone'])) {

                    $number = substr($data['phone'], 3, strlen($data['phone'])-3);

                    if (is_numeric($number) && !str_contains($number, '.')) {
                        
                        if ($this->checkSpanishNumber($number)) {

                            $information['phone'] = '[ERROR] Invalid spanish phone number. e.g: (+34) 5xx, 6xx, 7yx, 8xx, 800, 900, 80x, 90x.';
                            $comprobation = 1;

                        }

                    } else {

                        $information['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx or xxxxxxxxx both formats with 9 digits';
                        $comprobation = 1;

                    }
    
                } else {

                    $information['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx or xxxxxxxxx both formats with 9 digits';
                    $comprobation = 1;
                    
                }

            }

            if (strlen($data['phone']) == 9) {

                if (is_numeric($data['phone']) && !str_contains($data['phone'], '.')) {
                        
                    if ($this->checkSpanishNumber($data['phone'])) {

                        $information['phone'] = '[ERROR] Invalid spanish phone number. e.g: (+34) 5xx, 6xx, 7yx, 8xx, 800, 900, 80x, 90x.';
                        $comprobation = 1;

                    }

                } else {

                    $information['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx or xxxxxxxxx both formats with 9 digits';
                    $comprobation = 1;

                }

            }

            if (strlen($data['phone']) != 9 && strlen($data['phone']) != 12) {

                $information['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx (12 digits) or xxxxxxxxx (9 digits)';
                $comprobation = 1;

            }
        
        } else {

            $comprobation = 1;

        }

        //Updating phone number separately from profile picture
        if (!$comprobation) {
            
            if ($user->phone() !== $data['phone']) {

                $this->userRepository->addPhone($_SESSION['id'], $data['phone']);
                $information['phone'] = '[PROFILE] phone number updated.';

            }

            $phone = 'value=' . $data['phone'];
            
        } else {

            if(!empty($user->phone())) $phone = 'value=' . $user->phone();
            if(empty($user->phone())) $phone = '';

        }

        $comprobation = 0;

        //Checking pictures
        foreach ($uploadedFiles['files'] as $uploadedFile) {
            
            //Getting name of the file
            $name = $uploadedFile->getClientFilename();
            
            if (!empty($name)) {

                $fileInfo = pathinfo($name);
                $format = $fileInfo['extension'];

                //Getting temp file to check sizes
                $height = 0;
                $width = 0;
                $file = $_FILES["files"]['tmp_name'];
                list($width, $height) = getimagesize(implode('', $file));

                if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                    $information[] = sprintf(self::UNEXPECTED_ERROR, $uploadedFile->getClientFilename());
                    $comprobation = 1;
                }

                //Checking png, jpg
                if (!$this->isValidFormat($format)) {
                    $information[] = sprintf(self::INVALID_EXTENSION_ERROR, $format);
                    $comprobation = 1;
                }

                //Checking 1MB
                if ($uploadedFile->getsize() > 1024*1000) {
                    $information[] = sprintf(self::INVALID_SIZE_ERROR, $uploadedFile->getClientFilename());
                    $comprobation = 1;
                }

                //Checking 500x500
                if ($width > 500 || $height > 500) {
                    $information[] = sprintf(self::INVALID_DIM_ERROR, $format);
                    $comprobation = 1;
                }

                //Updating profile picture separately from phone number
                if (!$comprobation) {

                    $information['profile'] = '[PROFILE] profile picture updated.';
                    $this->userRepository->addPicture($_SESSION['id'], $uuid . '.' . $format);

                    // We generate a custom name here instead of using the one coming form the form
                    $uploadedFile->moveTo(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $uuid . '.' . $format);

                }

            }
            
        }

        $picture = $this->showPicture();

        return $this->twig->render(
            $response,
            'profile.twig',
            [
                'sessionStatus' => $_SESSION['logged'],
                'information' => $information,
                'username' => $user->username(),
                'email' => $user->email(),
                'birthday' => $user->birthday(),
                'phone' => $phone,
                'picture' => $picture,
            ]
        );

    }

    public function showChangePasswordAction(Request $request, Response $response): Response
    {
        
        $picture = $this->showPicture();

        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);

            return $this->twig->render(
                $response,
                'changePassword.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                ]);

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'login.twig',
                [
                   'sessionStatus' => $_SESSION['logged'],
                   'information' => $information,
                   'picture' => $picture,
                ]
            );

        }

    }

    public function changePasswordAction(Request $request, Response $response): Response
    {
        
        $comprobation = 0;
        $information = [];
        $picture = $this->showPicture();
        $user = $this->userRepository->getUser($_SESSION['id']);

        //Get data from POST
        $data = $request->getParsedBody();
        $oldPassword = $data['oldPassword'];
        $newPassword = $data['newPassword'];
        $confirmPassword = $data['confirmPassword'];

        //Check requirements of passwords
        $information = $this->validatePassword($oldPassword, $newPassword, $confirmPassword);

        if (count($information) == 0) {

            //Check old password
            if (strcmp(md5($oldPassword), $user->password()) != 0) {

                $information['change_result'] = '[PROFILE] old password does not match new password';
                $comprobation = 1;

            }

            //Check new password and confirm password
            if (strcmp($newPassword, $confirmPassword) != 0) {

                $information['change_result'] = '[PROFILE] new password does not match confirm password';
                $comprobation = 1;

            }

            if (!$comprobation) {

                $this->userRepository->changePassword($_SESSION['id'], md5($newPassword));
                $information['change_result'] = '[PASSWORD] password changed';

            }

        }

        return $this->twig->render(
            $response,
            'changePassword.twig',
            [
                'sessionStatus' => $_SESSION['logged'],
                'information' => $information,
                'picture' => $picture,
            ]
        );

    }

    private function validatePassword(string $oldPassword, string $newPassword, string $confirmPassword): array
    {
        
        $errors = [];

        //Check if password contains something
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {

            $errors['password_empty'] = '[ERROR] The passwords cannot be empty';

        } else {

            if (strlen($oldPassword) <= 6 || strlen($newPassword) <= 6 || strlen($confirmPassword) <= 6) $errors['password_min'] = '[ERROR] Each password must contain more than 6 characters';
            if (!preg_match('`[A-Z]`',$oldPassword) || !preg_match('`[A-Z]`',$newPassword) || !preg_match('`[A-Z]`',$confirmPassword)) $errors['password_upper'] = '[ERROR] Each password must contain an upper case letter';
            if (!preg_match('`[a-z]`',$oldPassword) || !preg_match('`[a-z]`',$newPassword) || !preg_match('`[a-z]`',$confirmPassword)) $errors['password_lower'] = '[ERROR] Each password must contain a lower case letter';
            if (!preg_match('`[0-9]`',$oldPassword) || !preg_match('`[0-9]`',$newPassword) || !preg_match('`[0-9]`',$confirmPassword)) $errors['password_number'] = '[ERROR] Each password must contain numbers';

        }

        return $errors;

    }

    private function isValidFormat(string $extension): bool
    {
        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    private function showPicture(): string 
    {

        $picture = '';

        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);

            //Checking actual picture
            if (!strcmp('default', $user->picture())) {
                $picture = 'assets/img/default_user.png';
            } else {
                $picture = 'uploads/' . $user->picture();
            }

        }

        return $picture;

    }

    private function checkSpanishNumber(string $number) : int
    {

        $oneDigitPrefix = substr($number, 0, 1);
        $twoDigitsPrefix = substr($number, 0, 2);
        $threeDigitsPrefix = substr($number,0, 3);

        if ($oneDigitPrefix == '5' || $oneDigitPrefix == '6' || $twoDigitsPrefix == '71' || $twoDigitsPrefix == '72' || $twoDigitsPrefix == '73' || $twoDigitsPrefix == '74' || $twoDigitsPrefix == '75' || $twoDigitsPrefix == '76' || $twoDigitsPrefix == '77' || $twoDigitsPrefix == '78' || $twoDigitsPrefix == '79'|| $oneDigitPrefix == '8' || $threeDigitsPrefix == '800' || $threeDigitsPrefix == '900' || $twoDigitsPrefix == '80' || $twoDigitsPrefix == '90') {

            return 0;

        }

        return 1;

    }

}