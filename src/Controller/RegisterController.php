<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;


use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



final class RegisterController
{

    private Twig $twig;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function showRegisterFormAction(Request $request, Response $response): Response
    {

        $picture = $this->showPicture();

        return $this->twig->render(
            $response,
            'register.twig',
            [
                'sessionStatus' => $_SESSION['logged'],
                'picture' => $picture,
            ]);
    }

    public function registerAction(Request $request, Response $response): Response
    {

        $picture = $this->showPicture();
        $data = $request->getParsedBody();

        if (empty($data['phone'])) $data['phone'] = '';

        $information = $this->validate($data);

        if ($information['register'] == 'OK'){
            
            $user = new User(
                $data['username'],
                $data['email'],
                md5($data['password']),
                md5($data['repeatPassword']),
                $data['birthday'],
                $data['phone'],
                'deactived',
                '0',
                'default',
                '',
                '',
                ''
            );

            $this->userRepository->save($user);
            $this->sendConfirmationMail($user);
            return $response->withHeader('Location','/login')->withStatus(200);

        } else {
            
            $information['register'] = '[REGISTER] failed';
            //Inform about errors in case of fail
            return $this->twig->render(
                $response,
                'register.twig',
                [
                'information' => $information,
                'sessionStatus' => $_SESSION['logged'],
                'picture' => $picture,
                ]
            );

        }

    }

    private function validate(array $data): array
    {

        $errors = [];
        $errors['register'] = 'OK';

        if (empty($data['username'])) {

            $errors['username'] = '[ERROR] The username cannot be empty.';
            $errors['register'] = 'KO';

        } else {

            if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $data['username']) || (!preg_match('`[A-Z]`',$data['username']) && !preg_match('`[a-z]`',$data['username'])) || !preg_match('`[0-9]`',$data['username'])) {
                $errors['username'] = '[ERROR] The username must contain a letter, a number and no-special chars. e.g: User1';
                $errors['register'] = 'KO';
            }

        }

        if (empty($data['password']) || empty($data['repeatPassword'])) {

            $errors['password'] = '[ERROR] The password cannot be empty.';
            $errors['register'] = 'KO';

        } else {

            if (strlen($data['password']) <= 6 || strlen($data['repeatPassword']) <= 6 || !preg_match('`[A-Z]`',$data['password']) || !preg_match('`[A-Z]`',$data['repeatPassword']) || !preg_match('`[a-z]`',$data['password']) || !preg_match('`[a-z]`',$data['repeatPassword']) || !preg_match('`[0-9]`',$data['password']) || !preg_match('`[0-9]`',$data['repeatPassword'])){
                
                $errors['password'] = '[ERROR] Password must contain six digits which include a upper case, a lower case and a number. e.g: Pass1234';
                $errors['register'] = 'KO';
            
            } else {

                if( $data['password'] !=  $data['repeatPassword']){

                    $errors['password'] = '[ERROR] Passwords do not match';
                    $errors['register'] = 'KO';
                
                }

            }

        }

        if (empty($data['email'])){

            $errors['email'] = '[ERROR] The email cannot be empty.';
            $errors['register'] = 'KO';

        } else {

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !str_contains($data['email'],'@salle.url.edu')) {

                $errors['email'] = '[ERROR] The email is not valid. e.g: someone@salle.url.edu';
                $errors['register'] = 'KO';
            
            }

        }

        if (empty($data['birthday'])) {

            $errors['birthday'] = '[ERROR] The birthday cannot be empty.';
            $errors['register'] = 'KO';

        } else {

            if (str_contains($data['birthday'], '-')) {

                $entryDateArray = explode('-', $data['birthday']);

                if (count($entryDateArray) == 3) {

                    if (preg_match('`[0-9]`', $entryDateArray[0]) && preg_match('`[0-9]`', $entryDateArray[1]) && preg_match('`[0-9]`', $entryDateArray[2])) {

                        if (checkdate((int)$entryDateArray[1], (int)$entryDateArray[2], (int)$entryDateArray[0])) {

                            $birthdayDate = new \DateTime($data['birthday']);
                            $todayDate =  new \DateTime();
                            $age = date_diff($todayDate,$birthdayDate);
                            
                            if ($age->y < 18){

                                $errors['birthday'] = '[ERROR] You must be at least 18 to register.';
                                $errors['register'] = 'KO';
                            
                            }

                        } else {

                            $errors['birthday'] = '[ERROR] The birthday does not belong to a real date.';
                            $errors['register'] = 'KO';

                        }

                    } else {

                        $errors['birthday'] = '[ERROR] The birthday format must be yyyy-mm-dd. e.g: 2000-12-12';
                        $errors['register'] = 'KO';

                    }

                } else {

                    $errors['birthday'] = '[ERROR] The birthday format must be yyyy-mm-dd. e.g: 2000-12-12';
                    $errors['register'] = 'KO';

                }

            } else {

                $errors['birthday'] = '[ERROR] The birthday format must be yyyy-mm-dd. e.g: 2000-12-12';
                $errors['register'] = 'KO';

            }

        }

        if(!empty($data['phone'])) {

            if (strlen($data['phone']) == 12) {

                if (preg_match('[^\+34]', $data['phone'])) {

                    $number = substr($data['phone'], 3, strlen($data['phone'])-3);

                    if (is_numeric($number) && !str_contains($number, '.')) {
                        
                        if ($this->checkSpanishNumber($number)) {

                            $errors['phone'] = '[ERROR] Invalid spanish phone number. e.g: (+34) 5xx, 6xx, 7yx, 8xx, 800, 900, 80x, 90x.';
                            $errors['register'] = 'KO';

                        }

                    } else {

                        $errors['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx or xxxxxxxxx both formats with 9 digits';
                        $errors['register'] = 'KO';

                    }
    
                } else {

                    $errors['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx or xxxxxxxxx both formats with 9 digits';
                    $errors['register'] = 'KO';
                    
                }

            }

            if (strlen($data['phone']) == 9) {

                if (is_numeric($data['phone']) && !str_contains($data['phone'], '.')) {
                        
                    if ($this->checkSpanishNumber($data['phone'])) {

                        $errors['phone'] = '[ERROR] Invalid spanish phone number. e.g: (+34) 5xx, 6xx, 7yx, 8xx, 800, 900, 80x, 90x.';
                        $errors['register'] = 'KO';

                    }

                } else {

                    $errors['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx or xxxxxxxxx both formats with 9 digits';
                    $errors['register'] = 'KO';

                }

            }

            if (strlen($data['phone']) != 9 && strlen($data['phone']) != 12) {

                $errors['phone'] = '[ERROR] Invalid phone number. e.g: +34xxxxxxxxx (12 digits) or xxxxxxxxx (9 digits)';
                $errors['register'] = 'KO';

            }
        
        }

        $user = new User(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['repeatPassword'],
            $data['birthday'],
            $data['phone'],
            'deactived',
            '0',
            'default',
            '',
            '',
            ''
        );

        $comprobation = $this->userRepository->register($user);

        if ($comprobation == 0 || $comprobation == 1) {

            $errors['username'] = '[ERROR] User already exists.';
            $errors['register'] = 'KO';
        
        }
        
        return $errors;
    
    }
    
    private function sendConfirmationMail(User $user):void
    {
        $Clientemail = $user->email();
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;                //Enable verbose debug output = > SMTP::DEBUG_SERVER
            $mail->isSMTP();                                      //Send using SMTP
            $mail->SMTPAuth = true;
            $mail->Host = 'smtp.gmail.com';            //Set the SMTP server to send through
            $mail->Username = 'pw2grupo07@gmail.com';
            $mail->Password = 'PW2grupo7';
            $mail->Port       = 587;                              //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above-->> 8025

            //Recipients
            $mail->setFrom('pw2grupo07@gmail.com', 'Grupo 7');
            $mail->addAddress($Clientemail);     //Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'LSTeam Register';

            $token = $this->userRepository->getID($user);

            $mail->Body    = '<a href="http://localhost:8030/activate?token=' . $token . '">CONFIRM YOUR REGISTRATION</a>' ;
            $mail->AltBody = 'You have successfully registered!'; //This is what client receives if not user HTML view

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    private function sendMoneyMail(string $emailDB):void
    {
        $Clientemail = $emailDB;
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;                //Enable verbose debug output = > SMTP::DEBUG_SERVER
            $mail->isSMTP();                                      //Send using SMTP
            $mail->SMTPAuth = true;
            $mail->Host = 'smtp.gmail.com';            //Set the SMTP server to send through
            $mail->Username = 'pw2grupo07@gmail.com';
            $mail->Password = 'PW2grupo7';
            $mail->Port       = 587;                              //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above-->> 8025

            //Recipients
            $mail->setFrom('pw2grupo07@gmail.com', 'Grupo 7');
            $mail->addAddress($Clientemail);     //Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'LSTeam Register';
            
            $mail->Body    = '<a href="http://localhost:8030/login">YOU NOW HAVE 50EUR TO BUY GAMES</a>';
            $mail->AltBody = 'You now have 50EUR to buy games!'; //This is what client receives if not user HTML view

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    public function sendRegisterComplete(Request $request, Response $response): response
    {

        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'];
        
        if (!$this->userRepository->checkDeactived((int)$token)) {

            $this->userRepository->addMoney((int)$token);
            $emailDB = $this->userRepository->getEmail((int)$token);
            $this->sendMoneyMail($emailDB);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();

            return $response->withHeader('Location', $routeParser->urlFor("login"));

        } else {

            $information['doubleRegistration'] = '[ERROR] user cannot be validate twice';

            return $this->twig->render(
                $response,
                'login.twig',
                [
                   'information' => $information,
                ]
            );

        }

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