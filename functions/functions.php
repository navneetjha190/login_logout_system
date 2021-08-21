<?php

use PHPMailer\PHPMailer\PHPMailer;

require './vendor/autoload.php';
include 'classes/config.php';

function clean($string){
    return htmlentities($string);
}

function redirect($location){

    return header("Location: {$location}");
}

function set_message($message){
    if(!empty($message)){
        $_SESSION['message']=$message;

    }else{
        $message="";
    }
}


function display_message(){
    if(isset($_SESSION['message'])){
        echo $_SESSION['message'];
        unset($_SESSION['message']);


    }
}

function token_generator(){

    $token=$_SESSION['token']=md5(uniqid(mt_rand(),true));

    return $token;
}

function validation_errors($error_message){
    $error_message=<<<DELIMITER


                

                <div class="alert alert-danger alert-dismissible " role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>

                <strong>Warning! </strong> $error_message
                </div>
DELIMITER;

return $error_message;

}

function email_exsists($email){

    $sql="SELECT id from users WHERE email= '$email'";
    $result= query($sql);

    if(row_count($result)==1){
        return true;
    }else{

        return false;
    }
}

function username_exsists($username){

    $sql="SELECT id from users WHERE username= '$username'";
    $result= query($sql);

    if(row_count($result)==1){
        return true;
    }else{

        return false;
    }
}


function send_email($email=null, $subject=null, $msg=null ,$headers=null){

    $mail= new PHPMailer();
    $mail->isSMTP();                                           
    $mail->Host       =Config::SMTP_HOST ;                  
    $mail->Username   = Config::SMTP_USER;                            
    $mail->Password   = Config::SMTP_PASSWORD;                               
    $mail->SMTPAuth   = true;        
    $mail->SMTPSecure = 'tls';            
    $mail->Port       = Config::SMTP_PORT;        
    $mail->isHTML(true);
    $mail->CharSet='UTF-8';


    $mail-> setFrom('yourgmailid@gmail.com');
    $mail-> addAddress($email);

    $mail->Subject = $subject;
    $mail->Body    = $msg;
    $mail->AltBody = $msg;

    
    if(!$mail->send()){

    echo 'Message could not be sent';
    echo "Mailer error:'.$mail->ErrorInfo";


} else{
    echo "Message has been sent";
}

    return mail($email, $subject, $msg ,$headers);

    

}



function validate_user_registration(){

    $errors=[];

    $min=3;
    $max=20;

    if($_SERVER['REQUEST_METHOD']=="POST"){
        

        $first_name     =clean($_POST['first_name']);
        $last_name     =clean($_POST['last_name']);
        $username     =clean($_POST['username']);
        $email     =clean($_POST['email']);
        $password     =clean($_POST['password']);
        $confirm_password     =clean($_POST['confirm_password']);

        if(strlen($first_name)< $min){
            $errors[]="Your first name cannot be less than {$min} characters";
        }

        if(strlen($first_name)> $max){
            $errors[]="Your first name cannot be greater than {$max} characters";
        }

        if(strlen($last_name)< $min){
            $errors[]="Your last name cannot be less than {$min} characters";
        }

        if(strlen($last_name)> $max){
            $errors[]="Your last name cannot be greater than {$max} characters";
        }

        if(strlen($username)< $min){
            $errors[]="Your username cannot be less than {$min} characters";
        }

        if(strlen($username)> $max){
            $errors[]="Your username cannot be greater than {$max} characters";
        }

        if(username_exsists($username)){
            $errors[]="Sorry that username is already registered";
        }

        if(email_exsists($email)){
            $errors[]="Sorry that email is already registered";
        }

        if(strlen($email)<$min){
            $errors[]="Your email cannot be less than {$min} characters";
        }

        if($password !== $confirm_password){

            $errors[]="Your password fields do not match";
        }


        if(!empty($errors)){
            foreach($errors as $error){
                echo validation_errors($error);              
        

               
            }
        } else {

            if(register_user($first_name, $last_name,$username, $email,$password)){

                set_message("<p class='bg-success text-center'>Please check your email or spam folder for an activation link</p>");
                redirect("index.php");
        }else {

            set_message("<p class='bg-danger text-center'>Sorry we could not register the user</p>");
                redirect("index.php");

        }


    }

}
}


function register_user($first_name, $last_name,$username, $email,$password){

        $first_name     =($_POST['first_name']);
        $last_name     =($_POST['last_name']);
        $username     =($_POST['username']);
        $email     =($_POST['email']);
        $password     =($_POST['password']);



    if(email_exsists($email)){
        return false;
    } else if(username_exsists($username)){
        return false;
    }else{

        $password =md5($password);
        $confirmation_code = md5($username);

        $sql= "INSERT INTO users(first_name,last_name,username,email , password ,confirmation_code,active)" ;
        $sql.= " VALUES('$first_name','$last_name','$username','$email','$password','$confirmation_code',0)";
        $result= query($sql);
        confirm($result);

        $subject="Activate account";
        $msg= "Please click the link below to activate your 
        
        <a href= \"http://localhost/login/activate.php?email=$email&code=$confirmation_code\">
        LINK IS HERE</a>";


        $headers="From: noreply@youtwebsite.com";


        send_email($email,$subject,$msg,$headers);
        

        return true;

        
    
    } 
        

}


function activate_user(){

    if($_SERVER['REQUEST_METHOD'] == "GET"){
        if(isset($_GET['email'])){
            $email=($_GET['email']);

            $confirmation_code= ($_GET['code']);

            
            $sql="SELECT id FROM users WHERE email= '$email' AND confirmation_code = '$confirmation_code' ";
            
            $result= query($sql);
         
           
            if(row_count($result)==1){
                
                $sql2="update users SET active=1 , confirmation_code=0 where email= '$email' AND confirmation_code= '$confirmation_code'";
                
                $result2=query($sql2);
                confirm($result2);
            set_message("<p class='bg-success'>Your Account has been activated, Please login</p>");
        
            redirect("login.php");
            }
        
        
        
        
        }
    }


}



function validate_user_login(){

    $errors=[];

    $min=3;
    $max=20;

    if($_SERVER['REQUEST_METHOD']=="POST"){

        $email     =clean($_POST['email']);
        $password     =clean($_POST['password']);

        $remember  =  isset($_POST['remember']);


        if(empty($email)){
            $errors[]="Email field cannot be empty";

        }

        if(empty($password))
        {
            $errors[]="Password cannot be empty";
        }



        if(!empty($errors)){
            foreach($errors as $error){
                echo validation_errors($error);              
        

               
            }
        } else {


            if(login_user($email,$password,$remember)){
                redirect("admin.php");
            }else{
                echo validation_errors("Your credentials are not correct");


            }

        }

    }

}


function login_user($email, $password,$remember){


    $sql="SELECT password,id from users WHERE email='$email' AND active=1";
    $result= query($sql);
    if(row_count($result)==1){
        

        $row=fetch_array($result);
        $db_password=$row['password'];
        if(md5($password)===$db_password){

            if($remember=="on"){
                setcookie('email',$email,time()+86400);
            }

            $_SESSION['email']=$email;
            
            return true;
        }else{
            
            return false;
        }



        return true;
    }else{
        return false;
    }


}


function logged_in(){

    if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
        return true;
    }else{
        return false;
    }
}


function recover_password(){
    if($_SERVER['REQUEST_METHOD']=="POST"){

        if(isset($_SESSION['token']) && $_POST['token']=== $_SESSION['token'])
        {

            $email=($_POST['email']);

            if(email_exsists($email)){
                $validation_code=md5($email + microtime());
              
                setcookie('temp_access_code',$validation_code,time()+900);
              
                $sq="UPDATE users SET confirmation_code='$validation_code' WHERE email='$email' ";
                $resul=query($sq);
                
              
              
                $subject="Please reset your password";
                $message="<h2>Here is your password reset code, click the link below or paste in the browser</h2> <h1>{$validation_code}</h1>

                <a href=\"http://localhost/login/code.php?email={$email}&code={$validation_code}\">http://localhost/login/code.php?email={$email}&code={$validation_code}</a>";
                
                
                
                $headers="From: noreply@yourwebsite.com";
                if(!send_email($email, $subject, $message, $headers)){
                   
                    echo validation_errors("Email could not be sent");
                }


                set_message("<p class='bg-success text-center'>Please check your email or span folder for password reset code</p>");
                redirect("index.php");






            }else{

                echo validation_errors("This email does not exsist");
            }


        
        }else{
            redirect("index.php");
        }
        
        if(isset($_POST['cancel_submit'])){
            redirect("login.php");
        }
    }
}

function validate_code(){
    if(isset($_COOKIE['temp_access_code'])){

            if(!isset($_GET['email'])&& !isset($_GET['code'])){

                redirect("index.php");
            }else if(empty($_GET['email'])|| empty($_GET['code'])){

                redirect("index.php");


            }else{
                if(isset($_POST['code'])){
                    $email=clean($_GET['email']);

                    $validation_code=clean($_POST['code']);
                    $sql="SELECT id from users where confirmation_code='$validation_code' and email='$email'";

                    $result=query($sql);

                    if(row_count($result)==1){


                        setcookie('temp_access_code',$validation_code,time()+900);


                        redirect("reset.php?email=$email&code=$validation_code");
                    }else{


                        echo "Wrong validation code";
                    }
                   

                }
            }
        


            

    }else{
        set_message("<p class='bg-danger text-center'>Sorry your validation cookie was expired</p>");

        redirect("recover.php");
        


        
    }
}

function password_reset(){
    if(isset($_COOKIE['temp_access_code'])){

        if(isset($_GET['email']) && isset($_GET['code'])){



        if(isset($_SESSION['token']) && isset($_POST['token'])){
        
        if($_POST['token']=== $_SESSION['token']){

            if($_POST['password'] === $_POST['confirm_password']){

                $updated_password=md5($_POST['password']);


               $sql="UPDATE users SET password='$updated_password', confirmation_code=0, active=1  where email= '$_GET[email]' ";

               query($sql);
               set_message("<p class='bg-danger text-center'>Your password has been updated, Please login </p>");

               redirect("login.php");
               

            }else{
                echo validation_errors("Password field don't match");
            }
        }


    }
}

}else{
    set_message("<p class='bg-danger text-center'>Sorry your time has expired</p>");

    redirect("recover.php");
    
}
}