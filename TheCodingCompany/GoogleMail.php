<?php
/**
 * Intellectual Property of the Coding Company AB All rights reserved.
 * 
 * @copyright (c) 2015, the Coding Company AB
 * @author V.A. (Victor) Angelier <vangelier@hotmail.com>
 * @version 1.0
 * @license http://www.apache.org/licenses/GPL-compatibility.html GPL
 * @package CodingCompany
 */

namespace CodingCompany;

/**
 * Description of GoogleMail
 *
 * @author Victor Angelier <vangelier@hotmail.com>
 * @package CodingCompany
 */
class GoogleMail
{
    /**
     * Mailbox connection
     * @var type 
     */
    protected $con = null;
    
    /**
     * Holds our e-mails
     * @var type 
     */
    protected $emails = null;
    
    /**
     * Show debug output
     * @var type 
     */
    protected $debug = false;

    /**
     * Whether or not to set messages to 'Seen' or 'Read' status
     * @var type 
     */
    protected $read_only = false;

    /**
     * Mailbox credentials
     * @var type 
     */
    protected $username = "";
    protected $password = "";
    
    /**
     * Inbox hostname
     * @var type 
     */
    protected $hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
    
    /**
     * Where to store attachments
     * @var type 
     */
    protected $filestore = "/home/domains/root/data";

    /**
     * Construct new GoogleMail object
     * 
     * @param type $username
     * @param type $password
     */
    public function __construct($username, $password){
        $this->username = $username;
        $this->password = $password;
    }
    
    public function read_mailbox(){
        if($this->connect()){
            if($this->open_inbox()){
                $this->get_emails();
            }
        }
    }
    
    /**
     * Connect to Google Mail IMAP
     */
    private function connect(){
        /* Open connection */
        $this->con = \imap_open(
                $this->hostname, 
                $this->username, 
                $this->password, 
                ($this->read_only ? OP_READONLY : null), 
                3, 
                array(
                    "DISABLE_AUTHENTICATOR" => "PLAIN"
                )) or die(print_r(imap_errors()));
        return $this->con;
    }
    
    /**
     * Open the mailbox and look for NEW message
     * @return type
     */
    private function open_inbox(){
        /* grab emails */
        $this->emails = imap_search($this->con, 'UNSEEN');
        return $this->emails;
    }
    
    /**
     * Get attachments
     * @param type $msg_number
     * 
     * Primary body type (value may vary with used library, use of constants is recommended)
        Value	Type	Constant
        0	text	TYPETEXT
        1	multipart	TYPEMULTIPART
        2	message	TYPEMESSAGE
        3	application	TYPEAPPLICATION
        4	audio	TYPEAUDIO
        5	image	TYPEIMAGE
        6	video	TYPEVIDEO
        7	model	TYPEMODEL
        8	other	TYPEOTHER
     */
    private function get_attachments($msg_number = 0){
        
        $structure = imap_fetchstructure($this->con, $msg_number);
        
        //Primary body type= 1 //Multi-part
        if($structure->type === 1){
            
            foreach($structure->parts as $partno => $part){
                
                switch($part->type){
                    //Image PNG/JPG
                    case 5 : 
                        //Handle the image part
                        $this->get_image_part($part, $msg_number, ($partno+1));
                        break;

                    //Application (pdf, etc)
                    case 3 :
                        //Handle attachement part
                        $this->handle_application_part($part, $msg_number, ($partno+1));
                        break;
                }
                    
            }
        }
    }
    
    /**
     * Handle application part (attachments like, pdf, zip etc)
     * 
     * @param type $part
     * @param type $msg_number
     * @param type $partno
     */
    private function handle_application_part($part, $msg_number, $partno){
        
        //Base64 encoded
        if($part->encoding === 3){

            //Grab the parameters
            if($part->ifdparameters){
                $parameter = $part->dparameters[0];
            }else{
                $parameter = $part->parameters[0];
            }

            //Inline uses NAME and attachments use FILENAME
            if($parameter->attribute === 'NAME' || $parameter->attribute === 'FILENAME'){
                $filename = $parameter->value;
            }
            
            //Debug message
            $this->debug($filename);
            
            $data = imap_fetchbody($this->con, $msg_number, $partno);

            //Save file
            $this->save_file($filename, base64_decode($data));
        }
    }
    
    /**
     * Write debug message
     * @param type $message
     */
    private function debug($message = ""){
        if($this->debug){
            echo $message."\r\n";
        }
    }
    
    /**
     *  Handle en get the image attachments
     * @param type $part
     * @param type $msg_number
     * @param type $structure
     * @param type $partno
     */
    private function get_image_part($part, $msg_number, $partno){
        
        //Base64 encoded
        if($part->encoding === 3){

            //Grab the parameters
            if($part->ifdparameters){
                $parameter = $part->dparameters[0];
            }else{
                $parameter = $part->parameters[0];
            }

            //Inline uses NAME and attachments use FILENAME
            if($parameter->attribute === 'NAME' || $parameter->attribute === 'FILENAME'){
                $filename = $parameter->value;
            }

            //Debug message
            $this->debug($filename);

            $data = imap_fetchbody($this->con, $msg_number, $partno);

            //Save image
            $this->save_file($filename, base64_decode($data));
        }
    }
    
    /**
     * Save file
     * @param type $filename
     * @param type $file_data
     */
    private function save_file($filename, $file_data){
        $res = @file_put_contents($this->filestore."/{$filename}", $file_data);
        if($res){
            $this->debug("File saved: ".$this->filestore."/{$filename}");
        }else{
            $this->debug("File NOT saved: ".$this->filestore."/{$filename}");
        }
    }
    
    /**
     * Check message for attachments
     * @param type $msg_number
     * 
     * Primary body type (value may vary with used library, use of constants is recommended)
        Value	Type	Constant
        0	text	TYPETEXT
        1	multipart	TYPEMULTIPART
        2	message	TYPEMESSAGE
        3	application	TYPEAPPLICATION
        4	audio	TYPEAUDIO
        5	image	TYPEIMAGE
        6	video	TYPEVIDEO
        7	model	TYPEMODEL
        8	other	TYPEOTHER
     */
    private function has_attachments($msg_number = 0){
        $structure = imap_fetchstructure($this->con, $msg_number);
        
        //Primary body type= 1 //Multi-part
        if($structure->type === 1){
            
            foreach($structure->parts as $part){
                                
                //Greater or eaqual to 3 (application)
                if($part->type >= 3){
                    return true;
                }
                
            }
        }
        return false;
    }
    
    /**
     * Get all e-mails, grab attachments and store them
     */
    private function get_emails(){
        if($this->emails){
            
            //Put newest e-mails on top
            rsort($this->emails);
            
            foreach($this->emails as $email_number) {
		
		/* get information specific to this email */
		$overview = imap_fetch_overview($this->con, $email_number, 0);
                
                //Debug message
                $this->debug($overview[0]->date."\r\n".$overview[0]->subject);
                
                //Check for and get attachments
                if($this->has_attachments($email_number)){
                    $this->get_attachments($email_number);
                }
                
            }
        }
        $this->close_connection();
    }
    
    /**
     *  Close connection
     * @return type
     */
    private function close_connection(){
        return imap_close($this->con);
    }
}
