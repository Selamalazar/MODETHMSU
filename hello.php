<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('trip_model');
    }
     

    /**
     * welcome $page,
     * 
     * @access public
     *
     * @return doesn't return Value.
     */
    public function index()
    {
        //pagination stuff, the rest of configurations are stored inside
        // the folder /config/pagination.php
        $config["per_page"] = 5;
        $config["base_url"] = site_url() . "/welcome/index/";
        $config["total_rows"] = $this->db->count_all('trip');
        
        
        $this->pagination->initialize($config);
        
        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $data["trips"] = $this->trip_model->get_trip(null, $config["per_page"], $page);
        $data["links"] = $this->pagination->create_links();

        $this->load->view('templates/header');
        $this->load->view('user/index', $data);
        $this->load->view('templates/footer');
    }
    

    /**
     * _check_user
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function _check_user($id)
    {
        $data['user'] = $this->user_model->get_user($id)->row();
        
        $this->load->view('templates/header');
        $this->load->view('user/index', $data);
        $this->load->view('templates/footer');
    }
    

    /**
     * only super admins, create another admin accounts.
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function create_admin()
    {
        if($this->user_model->is_super_admin())
        {
        
        //load libraries
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
        
        //set custome error message
        $this->form_validation->set_message('is_unique', '<strong>%s</strong> is already taken');
        
        //validate
        $this->form_validation->set_rules('first_name', '<strong>First name</strong>', 'required|max_length[31]');
        $this->form_validation->set_rules('last_name', '<strong>Last name</strong>', 'required|max_length[31]');
        $this->form_validation->set_rules('email', '<strong>Email address</strong>', 'required|is_unique[user.email]|valid_email|max_length[255]');
        $this->form_validation->set_rules('passconf', '<strong>Password confirmation</strong>', 'matches[password]');
        $this->form_validation->set_rules('password', '<strong>Password</strong>', 'required|min_length[8]|max_length[16]');
        
    
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('templates/header');
            $this->load->view('user/create_admin', $_POST);
            $this->load->view('templates/footer');
        }
        else
        {
            //create user actually
            //if true, the created user will be issued an admin right.
            $this->user_model->create_user(1);
            //send verification email
            //$this->_send_user_email($this->input->post('first_name'), $this->input->post('email'));
            //load view
            // $this->load->view('templates/header');
            // $this->load->view('user/add_user_success');
            // $this->load->view('templates/footer');

            // redirect
            redirect('welcome/admin');
        }
        } else { redirect('welcome'); }
    }

    /**
     * user sign up
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function sign_up()
    {
        
        //load libraries
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
        
        //set custome error message
        $this->form_validation->set_message('is_unique', '<strong>%s</strong> is already taken');
        
        //validate
        $this->form_validation->set_rules('first_name', '<strong>First name</strong>', 'required|max_length[31]');
        $this->form_validation->set_rules('last_name', '<strong>Last name</strong>', 'required|max_length[31]');
        $this->form_validation->set_rules('student_id', '<strong>Student ID</strong>', 'required|integer');
        $this->form_validation->set_rules('email', '<strong>Email address</strong>', 'required|is_unique[user.email]|valid_email|max_length[255]');
        $this->form_validation->set_rules('passconf', '<strong>Password confirmation</strong>', 'matches[password]');
        $this->form_validation->set_rules('password', '<strong>Password</strong>', 'required|min_length[8]|max_length[16]');
        
    
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('templates/header');
            $this->load->view('user/signup', $_POST);
            $this->load->view('templates/footer');
        }
        else
        {
            //create user actually
            $this->user_model->create_user(false);
            //send verification email
            $this->_send_user_email($this->input->post('first_name'), $this->input->post('email'));
            //load view
            // $this->load->view('templates/header');
            // $this->load->view('user/add_user_success');
            // $this->load->view('templates/footer');

            // redirect
            $this->index();
        }
    }
    
    

    /**
     * _send_user_email
     * 
     * @param string $first_name Description.
     * @param string $email      Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function _send_user_email($first_name = "", $email = "")
    {
        // the rest of $email configurations are stored inside a $config
        $this->load->library('email');
        //$this->email->initialize($config);
        
        $this->email->from('fiseha.tesfaw@moderneth.com', 'MSU Full Company Name');
        $this->email->to($email);
        $this->email->cc('fiseha.tesfaw@moderneth.com');
        $this->email->bcc('selam.alazar@moderneth.com');

        $this->email->subject('MSU User Registration Completed.');
        $this->email->message("Hi $first_name,\n\n Your registration completed successfully, \n\n cheers.");

        $this->email->send();

        
        //echo $this->email->print_debugger();
    }
    

    /**
     * profile
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function profile()
    {
        //if the user doesn't login, no need to bother, take them to login page.
        if(!$this->user_model->is_logged_in())
        {
            $this->load->view('templates/header');
            $this->load->view('user/login');
            $this->load->view('templates/footer');
            return;
        }
        
        //load libraries
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
        
        //custome error message, for users want to change email but input the same as the one that is already in db.
        $this->form_validation->set_message('is_unique', '<strong>%s</strong> is already taken by someone else or you didn\'t supply a new one. If you input the same email as the one you currently use, please leave the field empty.');
        
        //validate
        $this->form_validation->set_rules('email', '<strong>Email address</strong>', 'is_unique[user.email]|valid_email|max_length[255]');
        //$this->form_validation->set_rules('old_password', '<strong>Old password</strong>', 'required|min_length[8]');
        $this->form_validation->set_rules('new_password', '<strong>New password</strong>', 'min_length[8]');
        $this->form_validation->set_rules('passconf', '<strong>Password confirmation</strong>', 'matches[new_password]');
            
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('templates/header');
            $this->load->view('user/profile', $_POST);
            $this->load->view('templates/footer');
        }
        else
        {
            //Save changes  
            $email = $this->input->post('email'); //we don't always assume the form[email] is filled,
            if(empty($email)) //but ...
                $email = $this->session->userdata('email'); //what if user only want to change password and left empty email field?
            
                
            $this->user_model->update_user_profile(
                $this->session->userdata('id'),
                $email,
                $this->input->post('new_password')
            );
        
            //send notification email
            //$this->_send_user_email($this->input->post('first_name'), $this->input->post('email'));
            //load view
            $data['msg'] = "Information saved successfully";
            $this->load->view('templates/header');
            $this->load->view('user/profile', $data);
            $this->load->view('templates/footer');
        }
    }
    

    /**
     * login
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function login()
    {       
        
        //we don't need to show the "you need to login" msg twice already.
        $this->session->unset_userdata(array('msg' => ''));

        //load libraries
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
                
        //validate
        $this->form_validation->set_rules('email', '<strong>Email</strong>', 'required|valid_email|max_length[255]');
        $this->form_validation->set_rules('password', '<strong>Password</strong>', 'required');
        
            
        if ($this->form_validation->run() == FALSE)
        {
            
            
            $this->load->view('templates/header');
            $this->load->view('user/login', $_POST);
            $this->load->view('templates/footer');

            
        }
        else
        {
            //check user against the database
            if($this->user_model->login_user())
            {
                //redirect
                if ($this->session->userdata('role_id') < 1) {
                    redirect('/trip/list_trip');
                } else {
                    $redirect_to = $this->session->userdata("redirect_to_page");
                    

                    if($redirect_to)
                    {
                        //unset the session variable first
                        $this->session->unset_userdata(array("redirect_to_page" => ""));
                        redirect($this->session->userdata("redirect_to_page"));
                    }
                        
                    else
                    {
                        redirect('/');
                    }
                        
                }
            }
            else
            {
                //User name and password error
                $data['error_message'] = "<strong>Incorrect email address or password</strong>";
                $this->load->view('templates/header');
                $this->load->view('user/login', $data);
                $this->load->view('templates/footer');
            }
                
        }   
    
    }
    

    /**
     * logout
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function logout()
    {
        $this->user_model->logout_user();
        redirect('welcome');
    }
    

    /**
     * list_users
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function list_users()
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {
            $query = $this->user_model->get_none_admin_users();
            $data['users'] = $query;

            $this->load->view('templates/header');
            $this->load->view('user/list_user', $data);
            $this->load->view('templates/footer');
        } else { redirect('welcome'); }
    }

    /**
     * detail
     * 
     * @param mixed $user_id
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function detail($user_id)
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {
            $query = $this->user_model->get_user($user_id);
            $data['user'] = $query->row();

            $this->load->view('templates/header');
            $this->load->view('user/detail', $data);
            $this->load->view('templates/footer');  
        } else { redirect('welcome');}
    }

    /**
     * admin
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function admin() 
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {
        $data['admins'] = $this->user_model->get_admin()->result();

        $this->load->view('templates/header');
        $this->load->view('user/admin_list', $data);
        $this->load->view('templates/footer');
        } else { redirect('welcome');}
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/trip.php */