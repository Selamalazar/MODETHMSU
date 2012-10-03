<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Trip extends CI_Controller {

    /**
     * __construct
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('trip_model');
        $this->load->model('card_model');
        $this->load->library('dompdf_lib');
        $this->load->helper('file');
        
    }

    /**
     * this will take current user to a list of trips, if the user isn't
     * in the admin group, will be redirected to home page.
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function index()
    {
        $this->list_trip();
    }
        

    /**
     * create trip form, form validation
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function create_trip()
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {
            //load libraries
            $this->load->helper(array('form', 'url'));
            $this->load->library('form_validation');
            $this->load->library('upload');
            

            //set custome error message
            $this->form_validation->set_message('is_unique', '<strong>%s</strong> must be unique');
            
            //validate
            $this->form_validation->set_rules('name', '<strong>Trip name</strong>', 'required|max_length[199]');
            $this->form_validation->set_rules('status', '<strong>Trip status</strong>', 'required|integer');
            $this->form_validation->set_rules('depart_date', '<strong>Departure date</strong>', 'required');
            $this->form_validation->set_rules('return_date', '<strong>Return date</strong>', 'required');
            $this->form_validation->set_rules('price', '<strong>Price</strong>', 'required|integer');
            $this->form_validation->set_rules('description', '<strong>Trip description</strong>', 'required');      
            
            if ($this->form_validation->run() == FALSE)
            {
                $this->load->view('templates/header');
                $this->load->view('trip/add_trip', $_POST);
                $this->load->view('templates/footer');
            }
            else
            {           
                if ( ! $this->upload->do_upload())
                {
                    $error = array('error' => $this->upload->display_errors());
                    $this->load->view('templates/header');
                    $this->load->view('trip/add_trip', $error);
                    $this->load->view('templates/footer');  
                    
                }
                else
                {
                    $data = array('upload_data' => $this->upload->data());
                    $banner_file_name = $data['upload_data']['file_name'];
                    $this->trip_model->create_trip($banner_file_name);
                    $msg = 'Trip created successfuly.';

                    $this->list_trip($msg); 
                }
                
                }
        } else { redirect('welcome');}
    }

    /**
     * Students to sign-up for a trip.
     * 
     * @param mixed $trip_id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function sign_up($trip_id)
    {
        $data['trip_id'] = $trip_id;

        if($this->user_model->is_logged_in())
        {
            //count the number of cards user has
            $cards = $this->card_model->get_cards();

            //if the student has not used a credit card or registered one
            //before, he/she must do that first.
            if($cards->num_rows() < 1)
            {
                redirect('card');
            }
            else
            {
                //if already registered a card, let's show the list and
                //let the user choose which one to use.
                $data['cards'] = $this->card_model->get_cards();

                $this->load->view('templates/header');
                $this->load->view('trip/select_card', $data);
                $this->load->view('templates/footer');
            }
        }
        else
        {
            //we need to take the user to the trip detail page/trip sign up page optionally
            //afte he/she successfuly logged in.
            $redirect_user = array(
                "redirect_to_page" => "trip/detail/$trip_id",
                "msg" => "You need to login to sign up for a trip."
            );

            $this->session->set_userdata($redirect_user);

            $this->load->view('templates/header');
            $this->load->view('user/login');
            $this->load->view('templates/footer');
        }
        
    }

    /**
     * delete a trip, can only be done by super admin/admin
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function delete($id)
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {
            $data['trip_id'] = $id;
            $this->load->view('templates/header');
            $this->load->view('trip/delete_trip', $data);
            $this->load->view('templates/footer');
        } else { redirect('welcome'); }
    }

    /**
     * do the actual deletion, after confirmed.
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function do_delete($id)
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {
            $this->trip_model->delete($id);
            $msg = "Trip deleted successfuly.";
            $this->list_trip($msg);
        } else {redirect('welcome'); }
    }

    /**
     * Update trip information, this method only take the trip id, prep the data and
     * forward the user to the actual form.
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function edit($id)
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {

            $data['trip'] = $this->trip_model->get_trip($id)->row();
            
           
           //eho $this->trip_model->
            //a temporary data field to hold status of the keep_banner checkbox
            if($data['trip']->banner=="")
                $data['keep_banner'] ='';
            else
                $data['keep_banner'] ='on';


            $this->load->view('templates/header');
            $this->load->view('trip/edit', $data);
            $this->load->view('templates/footer');
        } else {redirect('welcome'); }
    }

    /**
     * Show the form, do the validation and commit the update to the database.
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function do_edit()
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {

            //load libraries
            $this->load->helper(array('form', 'url'));
            $this->load->library('form_validation');
            $this->load->library('upload');

            
            //a custom validation method to check and require file input            
            $this->form_validation->set_rules('keep_banner', 'Keep_banner', 'callback_check_change_image');

            //a temporary data field to hold status of the keep_banner checkbox
            $data['keep_banner']=$this->input->post('keep_banner');

            //set custome error message
            $this->form_validation->set_message('is_unique', '<strong>%s</strong> must be unique');
            
            //validate
            $this->form_validation->set_rules('name', '<strong>Trip name</strong>', 'required|max_length[199]');
            $this->form_validation->set_rules('status', '<strong>Trip status</strong>', 'required|integer');
            $this->form_validation->set_rules('depart_date', '<strong>Departure date</strong>', 'required');
            $this->form_validation->set_rules('return_date', '<strong>Return date</strong>', 'required');
            $this->form_validation->set_rules('price', '<strong>Price</strong>', 'required');
            $this->form_validation->set_rules('description', '<strong>Trip description</strong>', 'required'); 
            if ($this->form_validation->run() == FALSE)
            {
                $data['trip'] = $this->trip_model->get_trip($this->input->post('id'))->row();
                
                
                $this->load->view('templates/header');
                $this->load->view('trip/edit', $data);
                $this->load->view('templates/footer');
            }
            else
            {           
                
                
                $data['trip'] = $this->trip_model->get_trip($this->input->post('id'))->row();

                $old_banner= $data['trip']->banner;

                $banner_file_name="";
                
                //hold the existing image file name
               // $banner_file_name=$data['trip']->banner;

                //if the user chose to replace/add the banner image, 
                //then upload the new file and delete the old one
                if($this->input->post('keep_banner')!="on")
                {

                    if ($this->upload->do_upload())
                    {
                        $data = array('upload_data' => $this->upload->data());
                        $banner_file_name = $data['upload_data']['file_name'];

                        
                        //if a banner exists, then delete the old banner image file from system
                        if($old_banner!="")  
                            delete_files("uploads/".$old_banner);
                    }

                    else
                    {
                        $data['error'] = $this->upload->display_errors();

                        $this->load->view('templates/header');
                        $this->load->view('trip/edit', $data);
                        $this->load->view('templates/footer');  

                    }

                }

                $this->trip_model->update_trip($this->input->post('id'),$banner_file_name);
                
                echo '<br>'. base_url()."uploads/".$old_banner;
                echo '<br>'. base_url()."uploads/".$banner_file_name;

                //redirect('trip/list_trip');
                
            }
        } else {redirect('welcome');}
    }


    //this custom validation method is assinged to the keep_banner checkbox.
    //if the the checkbox is OFF then it means that the user must supply the file to replace with
    // 
    public function check_change_image($checked)
    {

        if($checked!='on')
        {
          //  echo "-----------" . $this->input->post('user_file');
            //$this->form_validation->set_rules('user_file', '<strong> Banner image file</strong>', 'required'); 
            if($_FILES['userfile']['name']=="")
            {
                $this->form_validation->set_message('check_change_image', 'No Image file selected.');
                return false;
            }
        }
        

        return true;
        // if ($str == 'test')
        // {
        //     $this->form_validation->set_message('username_check', 'The %s field can not be the word "test"');
        //     return FALSE;
        // }
        // else
        // {
        //     return TRUE;
        // }
    }

    /**
     * List all the trips.
     * 
     * @param string $msg messages propagete for UI purpose only, to show notification
     *                  e.g. Trip has been deleted successfuly... 
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function list_trip($msg = "")
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {

            //pagination
            $config["per_page"] = 10;
            $config["base_url"] = site_url() . "/trip/list_trip/";
            $config["total_rows"] = $this->db->count_all('trip');
            $config['uri_segment'] = 3;
            
            $this->pagination->initialize($config);
            
            $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
            $data["trips"] = $this->trip_model->get_trip(null, $config["per_page"], $page);
            $data["links"] = $this->pagination->create_links();
            $data["msg"] = ($msg) ? $msg : null ;
            //view
            $this->load->view('templates/header');
            $this->load->view('trip/list_trip', $data);
            $this->load->view('templates/footer');
        } else { redirect('welcome');}
    }

    /**
     * Show a dedicated page for a single trip.
     * 
     * @param mixed $trip_id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function detail($trip_id)
    {
        $query = $this->trip_model->get_trip($trip_id);
        
        $data['trip'] = $query->row();

        $this->load->view('templates/header');
        $this->load->view('trip/detail', $data);
        $this->load->view('templates/footer');  
    }

    /**
     * Who sign-up for a specific trip.
     * 
     * @param mixed $trip_id trip id.
     * @param mixed $export  If true, will send a PDF output header to the browser.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function registered_users($trip_id = NULL, $export = false)
    {
        if($this->user_model->is_admin() || $this->user_model->is_super_admin())
        {

            if(is_null($trip_id))
            {
                $this->list_trip();
            }
            else
            {
                $query = $this->trip_model->get_registered_users($trip_id);
                $data['registered_users'] = $query->result();

                if(!$export)
                {
                    //give only those filtered.
                    $this->load->view('templates/header');
                    $this->load->view('trip/registered_users', $data);
                    $this->load->view('templates/footer');  
                }
                else
                {
                    //This simply export the selected list to pdf.
                    $pdf_filename  = '/uploads/report.pdf'; 
                    $header = $this->load->view('trip/pdf_export_header','',true);
                    $body = $this->load->view('trip/registered_users_pdf', $data, true);
                    //$footer = $this->load->view('templates/footer','',true);  
                    $html = $body;
                    $this->dompdf_lib->convert_html_to_pdf($html, $pdf_filename, true);
                }
                
                
            }
        } else { redirect('welcome'); }
    }

}

/* End of file trip.php */
/* Location: ./application/controllers/trip.php */