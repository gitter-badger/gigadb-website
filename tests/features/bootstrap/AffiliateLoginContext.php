<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use PHPUnit\Framework\Assert;

/**
 * Features context.
 */
class AffiliateLoginContext extends BehatContext
{
    private $keys_map = array('Facebook' => array('api_key' => 'app_id', 'client_key' => 'app_secret'),
                               'Google' => array('api_key' => 'client_id', 'client_key' => 'client_secret'),
                               'Twitter' => array('api_key' => 'key', 'client_key' => 'secret'),
                               'LinkedIn' => array('api_key' => 'api_key', 'client_key' => 'secret_key'),
                               'Orcid' => array('api_key' => 'client_id', 'client_key' => 'client_secret'),
                           ); //TODO: extract this map into a module as it is also going to be used in UserIdentity's revoke_token

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }


//
// Place your definition and hook methods here:
//
//    /**
//     * @Given /^I have done something with "([^"]*)"$/
//     */
//    public function iHaveDoneSomethingWith($argument)
//    {
//        doSomethingWith($argument);
//    }
//

    /**
     * @Given /^test users are loaded$/
     */
    public function testUsersAreLoaded()
    {
        foreach ($this->keys_map as $key => $value) {
            Assert::assertTrue(null != $_ENV["${key}_tester_email"],"null != _ENV['${key}_tester_email']");
        }
    }


    /**
     * @Given /^Gigadb has a "([^"]*)" API keys$/
     */
    public function gigadbHasAApiKeys($arg1)
    {
        $_SERVER['REQUEST_URI'] = 'foobar';
        $_SERVER['HTTP_HOST'] = 'foobar';
        $opauthModule = $this->getMainContext()->getYii()->getModules()['opauth'];
        $api_key = $opauthModule['opauthParams']["Strategy"][$arg1][$this->keys_map[$arg1]['api_key']] ;
        $client_key = $opauthModule['opauthParams']["Strategy"][$arg1][$this->keys_map[$arg1]['client_key']] ;


        Assert::assertTrue('' != $api_key, "api_key for $arg1 is not empty");
        Assert::assertTrue('' != $client_key, "client_key for $arg1 is not empty");

    }




    /**
     * @Given /^I have a "([^"]*)" account$/
     */
    public function iHaveAAccount($arg1)
    {
        // throw new PendingException();
        Assert::assertTrue(null != $_ENV["${arg1}_tester_email"], "${arg1}_tester_email  is not empty");
        Assert::assertTrue(null != $_ENV["${arg1}_tester_password"], "${arg1}_tester_password is not empty");


    }


    /**
     * @Given /^The "([^"]*)" account has not authorised login to GigaDB web site$/
     */
    public function theAccountHasNotAuthorisedLoginToGigadbWebSite($arg1)
    {
        if ("Facebook" == $arg1) {
            $facebook = new Guzzle\Http\Client("https://graph.facebook.com");
            $request = $facebook->createRequest("DELETE");
            $request->setPath('/102733567195512/permissions');
             $request->getQuery()
                    ->set('access_token', $_ENV["${arg1}_access_token"]);
            $response = $facebook->send($request);
            $body = json_decode($response->getBody(true),true);
            Assert::assertTrue("true" == $body["success"],'Test users de-authorised');

        }

    }


    /**
     * @Given /^I don\'t have a Gigadb account for my "([^"]*)" account email$/
     */
    public function iDonTHaveAGigadbAccountForMyAccountEmail($arg1)
    {
        $email = $_ENV["${arg1}_tester_email"];
        $expected_nb_occurrences =  0 ;

        $nb_ocurrences = $this->countEmailOccurencesInUserList($email);
        Assert::assertTrue($expected_nb_occurrences == $nb_ocurrences, "I don't have a gigadb account for $email");

    }


    /**
     * @Given /^I have a Gigadb account for my "([^"]*)" account email$/
     */
    public function iHaveAGigadbAccountForMyAccountEmail($arg1)
    {
       $email = $_ENV["${arg1}_tester_email"];
       $expected_nb_occurrences =  1 ;

       $this->createNewUserAccountForEmail($email);


       $nb_ocurrences = $this->countEmailOccurencesInUserList($email);
        Assert::assertTrue($expected_nb_occurrences == $nb_ocurrences, "I have a gigadb account for $email");

    }

    /**
     * @Given /^I have a Gigadb account for my "([^"]*)" uid$/
     */
    public function iHaveAGigadbAccountForMyUid($arg1)
    {
       $uid = $_ENV["${arg1}_tester_uid"];
       $email = $_ENV["${arg1}_tester_email"];
       $expected_nb_occurrences =  1 ;

       $this->createNewUserAccountForUidAndEmail(strtolower($arg1),$uid,$email);


       $nb_ocurrences = $this->countEmailOccurencesInUserList($email);
        Assert::assertTrue($expected_nb_occurrences == $nb_ocurrences, "I have a gigadb account for $email");

    }

    /**
     * @Given /^I have a Gigadb account with a different email$/
     */
    public function iHaveAGigadbAccountWithADifferentEmail()
    {
       $email = "foo@bar.me";
       $expected_nb_occurrences =  1 ;

       $this->createNewUserAccountForEmail($email);


       $nb_ocurrences = $this->countEmailOccurencesInUserList($email);
        Assert::assertTrue($expected_nb_occurrences == $nb_ocurrences, "I have a gigadb account for $email");

    }



    /**
     * @When /^I click on the "([^"]*)" button$/
     */
    public function iClickOnTheButton($arg1)
    {
        $this->getMainContext()->clickLink($arg1);
    }

     /**
     * @When /^I sign in to "([^"]*)"$/
     */
    public function iSignInTo($arg1)
    {
        $login = $_ENV["{$arg1}_tester_email"];
        $password = $_ENV["${arg1}_tester_password"];

        if ($arg1 == "Twitter") {
            $this->getMainContext()->fillField("username_or_email", $login);
            $this->getMainContext()->fillField("password", $password);

            $this->getMainContext()->pressButton("Sign In");
        }
        else if ($arg1 == "Facebook") {
            $this->getMainContext()->fillField("email", $login);
            $this->getMainContext()->fillField("pass", $password);

            $this->getMainContext()->pressButton("loginbutton");

        }
        else if ($arg1 == "Google") {
            $this->getMainContext()->fillField("Email", $login);
            $this->getMainContext()->pressButton("Next");
            sleep(5);
            $this->getMainContext()->fillField("Passwd", $password);
            $this->getMainContext()->pressButton("Sign in");

        }
        else if ($arg1 == "LinkedIn") {
            $this->getMainContext()->fillField("session_key", $login);
            $this->getMainContext()->fillField("session_password", $password);
            $this->getMainContext()->pressButton("Allow access");

        }
        else if ($arg1 == "Orcid") {
            $this->getMainContext()->fillField("userId", $login);
            $this->getMainContext()->fillField("password", $password);
            $this->getMainContext()->pressButton("Sign into ORCID");
            sleep(15);

        }
        else {
            throw new Exception();
        }

        // $this->assertResponseStatus(200);
    }

    /**
     * @When /^I authorise gigadb for "([^"]*)"$/
     */
    public function iAuthoriseGigadbFor($arg1)
    {
        $session = $this->getMainContext()->getSession();
        $driver = $session->getDriver();

        if ($arg1 == "Twitter") {
            $this->getMainContext()->clickLink('click here to continue');
        }
        else if ($arg1 == "Facebook") {

            $xpath = '//button[@type="submit" and contains(., "Continue")]' ;
            $elements = $driver->find($xpath) ;

            if( 0 == count($elements) ) {
                throw new \Exception("The element is not found");
            }
            else {
                //print_r("pressing the button ". $driver->getHtml( $elements[0]->getParent()->getXpath() ) );
                $elements[0]->press();
            }

            // sleep(10);
            $this->getMainContext()->getSession()->wait(10000, '(typeof jQuery != "undefined" && 0 === jQuery.active)');



        }
        else if ($arg1 == "Google") {

            $this->getMainContext()->getSession()->wait(10000, '(typeof jQuery != "undefined" && 0 === jQuery.active)');
            $this->getMainContext()->pressButton("Allow");

        }
        else if ($arg1 == "Orcid") {
            $this->getMainContext()->getSession()->wait(15000, '(typeof jQuery != "undefined" && 0 === jQuery.active)');
            Assert::assertTrue($this->getMainContext()->getSession()->getPage()->hasField("enablePersistentToken"), "Authorize checkbox");
            Assert::assertTrue($this->getMainContext()->getSession()->getPage()->hasButton("authorize"), "Authorize button");
            $the_checkbox = $this->getMainContext()->getSession()->getPage()->findField("enablePersistentToken");
            $the_button = $this->getMainContext()->getSession()->getPage()->findButton("authorize");
            //var_dump($the_checkbox);
            //var_dump($the_button);
            $the_checkbox->check();
            sleep(5);
            $the_button->press();
            sleep(10);
        }
    }



    /**
     * @Then /^I\'m logged in into the Gigadb web site$/
     */
    public function iMLoggedInIntoTheGigadbWebSite()
    {
        $this->getMainContext()->assertPageContainsText("GigaDB Page");

    }

    /**
     * @Given /^a new Gigadb account is created with my "([^"]*)" details$/
     */
    public function aNewGigadbAccountIsCreatedWithMyDetails($arg1)

    {

        sleep(2);
        $email = $_ENV["${arg1}_tester_email"];
        if("Orcid" == $arg1) {
            $uid = $_ENV["${arg1}_tester_uid"];
            $email = "${uid}@Orcid";
        }

        $expected_nb_occurrences = 1;

        $nb_ocurrences = $this->countEmailOccurencesInUserList($email);
        Assert::assertTrue($expected_nb_occurrences == $nb_ocurrences, "Success creating a new gigadb account");

    }

     /**
     * @Given /^no new gigadb account is created for my "([^"]*)" account email$/
     */
    public function noNewGigadbAccountIsCreatedForMyAccountEmail($arg1)
    {
        $email = $_ENV["${arg1}_tester_email"];
        $expected_nb_occurrences = 1;

        $nb_ocurrences = $this->countEmailOccurencesInUserList($email);

        if ($expected_nb_occurrences != $nb_ocurrences) {
            throw new \Exception('Found '.$nb_ocurrences.' occurences of "'.$email.'" when expecting '.$expected_nb_occurrences);
        }
    }



    /* -------------------------------------------------------- utility functions and hooks -----------------*/


    public function countEmailOccurencesInUserList($email=null) {
        $nb_ocurrences = 0 ;
        print_r("Querying the database for emails... ");
        exec("vagrant ssh -c 'echo \"select email from gigadb_user;\" | sudo -Hiu postgres /usr/bin/psql -qtA gigadb'", $output, $err);
        $occurrences = array_keys($output, $email);

        $nb_ocurrences =  count($occurrences);
        // print_r("Found {$nb_ocurrences} of {$email}");
        return $nb_ocurrences;
    }

    private function createNewUserAccountForEmail($email) {
        $sql = "insert into gigadb_user(id, email,password,first_name, last_name, affiliation,role,is_activated,username) values(1,:'email','12345678','John','Doe','ETH','user',true,'johndoe')" ;
        $psql_command = "sudo -Hiu postgres /usr/bin/psql -v email='$email' gigadb" ;
        file_put_contents("sql/temp_command.sql", $sql);
        print_r("Creating a test user account... ");
        exec("vagrant ssh -c \"$psql_command < /vagrant/sql/temp_command.sql\"",$output);
        // var_dump($output);

    }

    private function createNewUserAccountForUidAndEmail($provider,$uid,$email) {
        $sql = "insert into gigadb_user(id, email,password,first_name, last_name, affiliation,role,is_activated,username,orcid_id) values(1,:'email','12345678','John','Doe','ETH','user',true,'johndoe',:'uid')" ;

       $psql_command = "sudo -Hiu postgres /usr/bin/psql -v email='$email' -v uid='$uid' gigadb" ;
        file_put_contents("sql/temp_command.sql", $sql);

        print_r("Creating a test user account... ");
        exec("vagrant ssh -c \"$psql_command < /vagrant/sql/temp_command.sql\"",$output);
        // var_dump($output);

    }


    public static function initialize_database()
    {
        print_r("Initializing the database... ");
        exec("vagrant ssh -c \"sudo -Hiu postgres /usr/bin/psql < /vagrant/sql/reset_user_table.sql\"",$kill_output);
        // var_dump($kill_output);
        sleep(5) ; # pad the adminstrative operations to cater for latency in order to avoid fatal error
    }


    /**
     * @BeforeScenario
    */
    public function initialize_session() {
        $this->getMainContext()->visit("/site/revoke");
        sleep(3);
        Self::initialize_database();
        clearstatcache() ;
    }

    /**
     * @AfterScenario
    */
    public function reset_stop_session($event) {

        $this->getMainContext()->visit("/site/revoke");
        sleep(3);
        $sqlfile = "sql/temp_command.sql" ;
        // $this->getMainContext()->getSession()->reset();
        $this->getMainContext()->getSession()->stop();
        if (file_exists($sqlfile) && $event->getResult() == 0) {
            $deleted =unlink($sqlfile);
            if (!$deleted) {
                throw new Exception("${sqlfile} could not be deleted");
            }
        }
    }

    /**
     * @AfterSuite
    */
    public static function reset_db($event) {

        if (true == $event->isCompleted()) {
            Self::initialize_database();
            print_r("Reloading test data... ");
            exec("vagrant ssh -c \"sudo -Hiu postgres /usr/bin/psql < /vagrant/sql/gigadb_testdata.sql\"",$kill_output);
        }
    }



    /**
     * @AfterStep
    */
    public function debugStep($event)
    {
        if ($event->getResult() == 4 ) {

            $this->getMainContext()->printCurrentUrl();

            try { # take a snapshot of web page

                $content = $this->getMainContext()->getSession()->getDriver()->getContent();
                $file_and_path = sprintf('%s_%s_%s',"content", date('U'), uniqid('', true)) ;
                file_put_contents("/tmp/".$file_and_path.".html", $content);

                if (PHP_OS === "Darwin" && PHP_SAPI === "cli") {
                    // exec('open -a "Preview.app" ' . $file_and_path.".png");
                    exec('open -a "Safari.app" ' . $file_and_path.".html");
                }
            }
            catch (Behat\Mink\Exception\DriverException $e) {
                print_r("Unable to take a snatpshot");
            }

        }
    }


}
