# LTI-2.0
The LTI 2.0 which allows  Tsugi  to be launched via the registration services and proxy

Depending on the administrator of the Tsugi server, you may be able to get an LTI key and secret for the use of the tools on the server. Tsugi has a capability to let you log in and request keys that can be enabled by the sytem owner.#

Tsugi supports both LTI 1.x and has support for LTI 2.0 but most uses of these tools in an LMS is LTI 1.x. So you most likely will need an LTI 1.x key.

You can plug your key, secret, and launch url (see below) into your LMS or test using any LTI Consumer harness such as the one I use at:
https://online.dr-chuck.com/sakai-api-test/lms.php


#Why LTI 2? 

LTI 2.0 allows :
-Auto-registration for zero-effort access to tool consumers for the learning system.
-Full, integrated REST services(level 3).
-Plug-and-play tool resources for new add-on-demand services
-There is a new family of LTI services (organization, tool settings, outomes, and more)


#How to set the environment in ruby?
Test with Ruby on Rails(Only environment available for a prosper test of the LTI 2.0)
```
-1- Obtain from IMS GLOBAL access to GitHub
-2-Follow clone and build instructions in repo readme files(lti_2c, lti2_tp)
-3-In a seperate shell starts the TCP and TP 


rails s --p 400
rails s --p 500


For a clear view about the whole process visit 
https://www.youtube.com/watch?v=3zTbtTldeiA&t=14s
```

#Tsugi implemented with LTI 2.0 

** Step1 **
Go to https://github.com/tsugiproject/tsugi
and clone the whole repo 

** Step2 **
Copy the whole Tsugi folder in the root of your local server.

** Step3 **
Go to the browser and launch Tsugi , assuming you have already configured the confi.php
```
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=tsugi';
// $CFG->pdo       = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi'; // MAMP
$CFG->dbuser    = 'root';
$CFG->dbpass    = '';
```
if running on mac OS, then change the following line

```
$CFG->mailsecret = 'warning:please-change-mailsecret-92ds29'; to $CFG->mailsecret = '';
```
** Step4 **
Login and upgrade the tables. 

**Step 5**

LTI 2.0 has main features the registration services (URL), the proxies, the TCP/IP
After some few hours exchanging with Mr John Tibbett we found out Tsugi has already been implemented with the LTI 2.0 and according to
him Dr Chuck has alread amended the LTI 2.1 might be already available and implemented.

 
Tsugi has already the LTI 2.0 version inplemented

Open the file lti2.php, and find the line 64 and 74

// We have a person authorized to use LTI 2.0 on this server

```
$_POST = $_SESSION['lti2post'];

$lti_message_type = $_POST["lti_message_type"];
$re_register = $lti_message_type == "ToolProxyReregistrationRequest";

```


Right here the values of the URL are return according to what has been parsed
```
$return_url_status = false;
$return_url_tool_guid = false;
$return_url_lti_msg = false;
$return_url_lti_errormsg = false;

```

The registration of the user logged must have its own key 
The key has to be unique and must not belong to other user
And then we double check the registration of the scenarion in a transaction later on.

```
$tool_proxy_guid = false;
$tool_proxy_guid_from_consumer = isset($_POST['tool_proxy_guid']);
if ( $re_register ) {
        $oauth_consumer_key = $_POST['oauth_consumer_key'];
        $reg_key = $oauth_consumer_key;
        $tool_proxy_guid = $oauth_consumer_key;
        $tool_proxy_guid_from_consumer = true;
        $key_sha256 = lti_sha256($oauth_consumer_key);
        echo("key_sha256=".$key_sha256."<br>");
        $oldproxy = $PDOX->rowDie(
            "SELECT secret
                FROM {$CFG->dbprefix}lti_key
                WHERE user_id = :UID AND key_sha256 = :SHA LIMIT 1",
            array(":SHA" => $key_sha256,
                ":UID" => $_SESSION['id'])
        );
        $reg_password = $oldproxy['secret'];
        if ( strlen($reg_password) < 1 ) {
            lmsDie("Registration key $reg_key cannot be re-registered.");
        }
} else if ( $lti_message_type == "ToolProxyRegistrationRequest" ) {
        $reg_key = $_POST['reg_key'];
        $tool_proxy_guid = isset($_POST['tool_proxy_guid']) ? $_POST['tool_proxy_guid'] : $reg_key;
        $oauth_consumer_key = $tool_proxy_guid;
        $key_sha256 = lti_sha256($tool_proxy_guid);
        echo("key_sha256=".$key_sha256."<br>");
        $oldproxy = $PDOX->rowDie(
            "SELECT user_id
                FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :SHA LIMIT 1",
            array(":SHA" => $key_sha256)
        );
        if ( is_array($oldproxy) && $oldproxy['user_id'] != $_SESSION['id'] ) {
            lmsDie("Registration key $reg_key cannot be registered.");
        }
        $reg_password = $_POST['reg_password'];
} else {
        echo("</pre>");
        lmsDie("lti_message_type not supported ".$lti_message_type);
}

```

On the line 255 we found the registration URL, afterwards we dump the services 


```
echo("<pre>\n");
$tc_services = $tc_profile->service_offered;
echo("Found ".count($tc_services)." services profile..\n");
if ( count($tc_services) < 1 ) lmsDie("At a minimum, we need the service to register ourself - doh!\n");


```

If the URL registered is not found return the message otherwise return true by saying an application is found 

```
if ( $register_url == false ) lmsDie("Must have an application/vnd.ims.lti.v2.toolproxy+json service available in order to do tool_registration.");

// unset($_SESSION['result_url']);
// if ( $result_url !== false ) $_SESSION['result_url'] = $result_url;

echo("\nFound an application/vnd.ims.lti.v2.toolproxy+json service - nice for us...\n");

```

Right here wee are ready to send the registration . There is a time to set up the key for the server side

```
$key_sha256 = lti_sha256($oauth_consumer_key);
echo("key_sha256=".$key_sha256."<br>");

echo("</pre>\n");

// Get the ack value
$ack = false;
if ( $re_register ) {
    $ack = bin2hex(openssl_random_pseudo_bytes(10));
}

// Lets register!
$OUTPUT->togglePre("Registration Request",htmlent_utf8($body));

$more_headers = array();
if ( $ack !== false ) {
    $more_headers[] = 'VND-IMS-CONFIRM-URL: '.$CFG->wwwroot.
        '/lti/tp_commit.php?commit='.urlencode($ack);
}

$response = LTI::sendOAuthBody("POST", $register_url, $reg_key, $reg_password, "application/vnd.ims.lti.v2.toolproxy+json", $body, $more_headers, $hmac256);

$response_code = Net::getLastHttpResponse();

global $LastOAuthBodyBaseString;
$OUTPUT->togglePre("Registration Request Headers",htmlent_utf8(Net::getBodySentDebug()));
$OUTPUT->togglePre("Registration Request Base String",$LastOAuthBodyBaseString);
echo("<p>Http Response code = $response_code</p>\n");
$OUTPUT->togglePre("Registration Response Headers",htmlent_utf8(Net::getBodyReceivedDebug()));

```


