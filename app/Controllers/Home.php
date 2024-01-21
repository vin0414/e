<?php

namespace App\Controllers;
use App\Libraries\Hash;
use Config\App;

class Home extends BaseController
{
    private $db;
    public function __construct()
    {
        helper('text');
        $this->db = db_connect();
    }

    public function index()
    {
        return view('welcome_message');
    }

    //admin
    public function Auth()
    {
        return view('auth');
    }

    public function check()
    {
        
    }

    public function logout()
    {
        if(session()->has('loggedUser'))
        {
            session()->remove('loggedUser');
            return redirect()->to('/auth?access=out')->with('fail', 'You are logged out!');
        }
    }

    public function Dashboard()
    {
        return view('admin/index');
    }

    //customer

    public function login()
    {
        return view('login');
    }

    public function register()
    {
        return view('/register');
    }

    public function customerLogin()
    {

    }

    public function createAccount()
    {
        $customerModel = new \App\Models\customerModel();
        //data
        $emailadd = $this->request->getPost('email');
        $fullname = $this->request->getPost('fullname');
        $password = $this->request->getPost('password');
        $retype = $this->request->getPost('confirm_password');

        $validation = $this->validate([
            'email'=>'required|valid_email|is_unique[tblcustomer.EmailAddress]',
            'fullname'=>'required',
            'password'=>'required',
            'confirm_password'=>'required',
        ]);

        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Email already exists');
            return redirect()->to('/register')->withInput();
        }
        else
        {
            if($password!=$retype)
            {
                session()->setFlashdata('fail','Invalid! Password mismatched');
                return redirect()->to('/register')->withInput();
            }
            else
            {
                $token_code = random_string('alnum',20);
                $hash_password = Hash::make($password);
                $values = [
                    'Email'=>$emailadd, 'Password'=>$hash_password ,'Fullname'=>$fullname,'Status'=>0,'Token'=>$token_code
                ];
                $customerModel->save($values);
                $email = \Config\Services::email();
                $email->setTo($emailadd,$fullname);
                $email->setFrom("pcos.system2024@gmail.com","e-PCOS");
                $imgURL = "assets/img/logo.png";
                $email->attach($imgURL);
                $cid = $email->setAttachmentCID($imgURL);
                $template = "<center>
                <img src='cid:". $cid ."' width='100'/>
                <table style='padding:20px;background-color:#ffffff;' border='0'><tbody>
                <tr><td><center><h1>Account Activation</h1></center></td></tr>
                <tr><td><center>Hi, ".$fullname."</center></td></tr>
                <tr><td><p><center>Please click the link below to activate your account.</center></p></td><tr>
                <tr><td><center><b>".anchor('activate/'.$token_code,'Activate Account')."</b></center></td></tr>
                <tr><td><p><center>If you did not sign-up in e-PCOS Website,<br/> please ignore this message or contact us @ pcos.system2024@gmail.com</center></p></td></tr>
                <tr><td>e-PCOS IT Support</td></tr></tbody></table></center>";
                $subject = "Account Activation | e-PCOS";
                $email->setSubject($subject);
                $email->setMessage($template);
                $email->send();
                session()->set('customer_email', $emailadd);
                return redirect()->to('/success');
            }
        }
    }

    public function successPage()
    {
        return view('success-form');
    }

    public function activate($id)
    {
        $customerModel = new \App\Models\customerModel();
        $customer = $customerModel->WHERE('Token',$id)->first();
        $values = ['Status'=>1];
        $customerModel->update($customer['customerID'],$values);
        session()->set('sess_id', $customer['customerID']);
        session()->set('sess_fullname', $customer['Fullname']);
        session()->set('customer_email',$customer['EmailAddress']);
        return $this->response->redirect(site_url('customer/dashboard'));
    }

    public function signOut()
    {
        if(session()->has('customer_email'))
        {
            session()->remove('customer_email');
            session()->remove('sess_id');
            return redirect()->to('/login?access=out')->with('fail', 'You are logged out!');
        }
    }
}
