<?php 
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

if(!file_exists(ROOT.DS.'config.inc.php'))
    exit('Error! set up config.inc.php first');
include_once(ROOT.DS.'config.inc.php');
//catch legacy configurations
if(!defined('CRYPTO'))
    define('CRYPTO','BTC');
date_default_timezone_set((TIMEZONE?TIMEZONE:'Europe/Paris'));
include_once(ROOT.DS.'vendor/autoload.php');
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Sell;
use Coinbase\Wallet\Resource\Buy;
use Coinbase\Wallet\Value\Money;

$t = new trader(($argv[1]==='debug'?true:false));

$myname = $argv[0];
    
switch($argv[1])
{    

    case 'check':
        $t->mainCheck();
    break;

    case 'report':
        $t->report();
    break;

    case 'debug':
        $t->debug();
    break;

    case 'vendre':
        $curseur = $argv[2];
        $t->vendre($curseur);
    break;
    case 'account':
        // $curseur = $argv[2];
        //$sellat = $argv[3];
        $t->myAccount();
    break;
    case 'process':
        $t->Process();
    break;
    case 'order':
        $t->Order();
    break;
    case 'Start':
        $t->Start();
    break;

    default:
        echo "Usage info\n---------------\n";
    break;
}

class trader
{
    public $buyPrice;
    public $sellPrice;
    public $spotPrice;
    public $lastSellPrice;
    
    private $client;
    private $account;
    private $wallet;
    private $transactions;
    private $traderID;
    private $currencyWallet;

    private $redis;


    function __construct($noinit=false)
    {
        
        $configuration = Configuration::apiKey(COINBASE_KEY, COINBASE_SECRET);
        $this->client = Client::create($configuration);
        if($noinit===true) return;

        $accounts = $this->client->getAccounts();
        foreach($accounts as $account)
        {
            //echo "[W] Found wallet:\t '".$account->getName()."'\n";
            if($account->getCurrency()==CRYPTO)
            {
                $this->account = $account;

                echo "[i] Will use '".$account->getName()."' as crypto wallet :)\n";
            }
            else if($account->getCurrency()==CURRENCY)
            {
                $this->currencyWallet = $account;
                echo "[i] Will use '".$account->getName()."' as currency wallet :)\n";
            }
        }
        if(!$this->account)
        {
            $this->account = $this->client->getPrimaryAccount();
            echo "[W] Didn't find your '".CRYPTO." Wallet' Account.. falling back to default\n";
        }


        $paymentMethods = $this->client->getPaymentMethods();

        //find wallet ID
        foreach($paymentMethods as $pm)
        {
            if($pm->getName() == CURRENCY.' Wallet')
            {
                $this->wallet = $pm;
                echo "[i] Will use ".$pm->getName()." for payments\n";
                break;
            }
        }
        if(!$this->wallet)
            exit("[ERR] Could not find your payment method: '".CURRENCY." Wallet'. Are you sure ".CURRENCY." is a currency?\n");

        echo "\n";

        //$this->checkBalanceOfAccount($this->account);
        $this->updatePrices();
    }


function Start()
{
    try
      {
          $this->Process();
      }
      catch (Process $e)
      {
        $object = "Error: Your Server Stop !";
        $message = "Error Code : ".$e;
            $this->AlertMail($object, $message);
            echo "SendMail for Error ! \n";
      }

}





// Buying
function Buying($order_spot, $spot, $order_id, $order_type, $order_value, $date, $My_Btc, $My_Btc_to_Euro, $message, $conn)
{
        //  Buy : 
                    //      $order_spot = Spot Price for 1 BTC to Buy
                    //      $spot = Actualy Spot Important
                    //      $order_id = For links a Trade with this order
                    //      $order_type = For save in DB Sell | Buy 
                    //      $order_value = How Many Selling ?
                    //      $My_Btc = Preparate Buying
                    //      $My_Btc_to_Euro = wait ...
                    //      $message = for AlertMail()
                    //      $date = for date
                    //      $conn = for connection DB
                    //
        if ($order_spot > $spot) {
            echo "Buy $init_value \n";

            // Insert Transaction for Archive
            $query= "INSERT INTO transactions (id, id_ordre, type, value, spot, date_time, My_Btc, My_Btc_to_Euro) VALUES (DEFAULT, '$order_id', '$order_type','$order_value', '$order_spot', '$date', '$My_Btc', '$My_Btc_to_Euro')";
            $result = $conn->query($query);

            if ($result === TRUE) {

                // Calcul Tax ~1,99 for 30€
                $buying = (floatval(str_replace(',', '.',$init_value)-"1.99"));
                $object = "Alert: Buying : $buying | Spot : $spot !";
                $this->AlertMail( $object, $message);
                echo "SendMail for Alert ! \n";

                // Buying
                    $this->buyBTC($buying);

                // Set Executed this Order
                $query = "UPDATE ordres SET date_time='$date', executed='1' WHERE id='$order_id'";
                // Exécution de la requête
                $result = $conn->query($query);

                echo "Transaction created successfully \n";

            } else {
                echo "Error creating table: \n" . $conn->error;
                die;
            }
        }
}



function Selling($order_spot, $spot, $order_id, $order_type, $order_value, $My_Btc, $My_Btc_to_Euro, $message, $date, $conn)
{
        //  Sell : 
                    //      $order_spot = Spot Price for 1 BTC to Sell
                    //      $spot = Actualy Spot Important
                    //      $order_id = For links a Trade with this order
                    //      $order_type = For save in DB Sell | Buy 
                    //      $order_value = How Many Selling ?
                    //      $My_Btc = Preparate Selling
                    //      $My_Btc_to_Euro = wait ...
                    //      $message = for AlertMail()
                    //      $date = for date
                    //      $conn = for connection DB
                    //
        if ($order_spot < $spot) {
            echo "Sell $order_value \n";

            // Insert Transaction for Archive
            $query= "INSERT INTO transactions (id, id_ordre, type, value, spot, date_time, My_Btc, My_Btc_to_Euro) VALUES (DEFAULT, '$order_id', '$order_type','$order_value', '$order_spot', '$date', '$My_Btc', '$My_Btc_to_Euro')";
            $result = $conn->query($query);

            if ($result === TRUE) {

                $selling = (floatval(str_replace(',', '.',$order_value)-"1.99"));
                $object = "Alert: Selling : $selling | Spot : $spot !";
                $this->AlertMail( $object, $message);
                echo "SendMail for Alert ! \n";

                // Selling
                     $this->sellBTC($selling);

                // Set Executed this Order
                $query = "UPDATE ordres SET date_time='$date', executed='1' WHERE id='$order_id'";
                // Exécution de la requête
                $result = $conn->query($query);

                echo "Transaction created successfully \n";

            }else{
                echo "Error creating table: \n" . $conn->error;
                die;
            }
        }
}

function AlertMail($object, $message)
    {
        // AlertMail
        //

        $headers = 'From: coinbase@slote.me' . "\r\n" .
         'Reply-To: coinbase@slote.me' . "\r\n" .
         'X-Mailer: PHP/' . phpversion();
        mail('mediashare.supp@gmail.com', $object, $message, $headers);
    }


function History($conn)
{
        // History
        //

        // Color Init
        $Light_Green = "\33[1;32m";
        $Light_Red = "\33[1;31m";
        $White = "\33[1;37m";

        // Get Logs in DB
        $query= "SELECT * FROM transactions WHERE 1";
        $history = $conn->query($query);

        echo "\n  [H] History \n";
        echo "\n    [H:L] Log \n";

        // Init Default value;
        $history_buy = 0;
        $history_sell = 0;
        $count_log = 0;

        // Pagination
        foreach ($history as $key => $row) {
            $count_log++;
        }
            // This Get 5 Last entry on Transactions Table
            $count_show = $count_log - 5;

        $count = 0;
        foreach ($history as $key => $row) {
            $count++;
            $history_id = $row['id'];
            $history_value = $row['value'];
            $history_type = $row['type'];
            $history_date = $row['date_time'];
            $history_btc = $row['My_Btc'];
            $history_euro = $row['My_Btc_to_Euro'];
            $history_spot = $row['spot'];

            // Veryf Sell | Buy 
            if ($history_type == 0) {
                $history_sell = $history_sell + $history_value;
                $history_title = "Sell";
                $history_color = $Light_Red;
            }elseif ($history_type == 1) {
                $history_buy = $history_buy + $history_value;
                $history_title = "Buy";
                $history_color = $Light_Green;
            }
            if ($count > $count_show) {
                echo "          [H:L:$history_title] $history_color".$history_value."$White €\n";
                echo "              [H:L:$history_id] Wallet : $history_color".$history_euro."$White €\n";
                echo "              [H:L:$history_id] BTC : $history_color".$history_btc."$White BTC\n";
                echo "              [H:L:$history_id] Spot : $history_color".$history_spot."$White €\n";
                echo "              [H:L:$history_id] Date : ".$history_date."\n";
            }
        }
        echo "\n";
        echo "      [H:Sell] $Light_Green".$history_sell."$White €\n";
        echo "      [H:Buy] $Light_Red".$history_buy."$White €\n";


}

// Initialize all Orders
function Initialize($spot, $My_Btc, $My_Btc_to_Euro, $date, $conn)
{
        //  Order : 
        //      $spot = Spot Price for 1 BTC = $spot €
        //      $My_Btc = 
        //      $My_Btc_to_Euro = Follow evolution
        //      $date = for date
        //      $conn = for connection DB
        //

        // Init Color
        $Black = "\33[0;30m";
        $Green = "\33[0;32m";
        $Cyan = "\33[0;36m";
        $Red = "\33[0;31m";
        $Purple = "\33[0;35m";
        $Yellow = "\33[1;33m";
        $White = "\33[1;37m";

        // Get Order in Ordres Table
        $query= "SELECT * FROM ordres Order by id ASC";
        $orders = $conn->query($query);

        echo "  [I] Initialize \n";
        foreach ($orders as $key => $row) {
            $order_id = $row['id'];
            $order_value = $row['value'];
            $order_spot = $row['spot'];

            // For Type 0 = Sell | 1 = Buy
            $order_type = $row['type'];
            $order_date = $row['date_time'];
            $order_online = $row['online'];
            $order_executed = $row['executed'];
            
            if ($order_executed == false) {
                if ($order_online == true) {
                    $diff_spot = ($spot-$order_spot);
                    $prevision = ($My_Btc*$order_spot);
                    $diff_target = ($My_Btc_to_Euro-$prevision);
                 
                 // 0 = Sell | 1 = Buy
                 if ($order_type == 0) {
                        $title = "Sell";
                        $message = "| [$title] Order n°".$order_id." \n
                        | [$title] $title for: ".$order_value." € \n
                        | [$title] Ratio:    ".$diff_target." € \n
                        | [$title:MyBtc:€]   ".$My_Btc_to_Euro." € \n
                        |     [I:Spot:Target] ".$order_spot." € \n
                        |     [I:Spot:Diff]   ".$diff_spot." € \n
                        |     [I:Spot:Market] ".$spot." € \n
                        | ___________________________________\n";

                    
                        
                        //  Selling : 
                        //      $order_spot = Spot Price for 1 BTC to Sell
                        //      $spot = Actualy Spot Important
                        //      $order_id = For links a Trade with this order
                        //      $order_type = For save in DB Sell | Buy 
                        //      $order_value = How Many Selling ?
                        //      $My_Btc = Preparate Selling
                        //      $My_Btc_to_Euro = wait ...
                        //      $message = for AlertMail()
                        //      $date = for date
                        //      $conn = for connection DB
                        //
                        $this->Selling($order_spot, $spot, $order_id, $order_type, $order_value, $My_Btc, $My_Btc_to_Euro, $message, $date, $conn);
                    }
                    // 0 = Sell | 1 = Buy
                    if ($order_type == 1) {
                            $title = "Buy";
                            $message = "| [$title] Order n°".$order_id." \n
                            | [$title] $title for: ".$order_value." € \n
                            | [$title] Ratio:    ".$diff_target." € \n
                            | [$title:MyBtc:€]   ".$My_Btc_to_Euro." € \n
                            |     [I:Spot:Target] ".$order_spot." € \n
                            |     [I:Spot:Diff]   ".$diff_spot." € \n
                            |     [I:Spot:Market] ".$spot." € \n
                            | ___________________________________\n";
  
                        
                        //  Buying : 
                        //      $order_spot = Spot Price for 1 BTC to Buy
                        //      $spot = Actualy Spot Important
                        //      $order_id = For links a Trade with this order
                        //      $order_type = For save in DB Sell | Buy 
                        //      $order_value = How Many Buying ?
                        //      $My_Btc = Preparate Selling
                        //      $My_Btc_to_Euro = wait ...
                        //      $message = for AlertMail()
                        //      $date = for date
                        //      $conn = for connection DB
                        //
                        $this->Buying($order_spot, $spot, $order_id, $order_type, $order_value, $My_Btc, $My_Btc_to_Euro, $message, $date, $conn);
                        
                    }
                    echo "   | [$title] Order n°$Purple".$order_id."$White\n";
                    echo "   | [$title] $title for: $Yellow".$order_value."$White €\n";
                    echo "   | [$title] Ratio:    $Red".$diff_target."$White €\n";
                    echo "   | [$title:MyBtc:€]   $Cyan".$My_Btc_to_Euro."$White €\n";
                    echo "   |     [I:Spot:Target] $Green".$order_spot."$White €\n";
                    echo "   |     [I:Spot:Diff]   $Red".$diff_spot."$White €\n";
                    // echo "          [I:Euro:Diff]   $Red".$ratio_target."$White €\n";
                    echo "   |     [I:Spot:Market] $Cyan".$spot."$White €\n";
                    echo "   | ___________________________________\n";
                    
                }
            }
        }   
}


function Flux($spot, $last_spot, $spot_color, $jump_spot, $My_Btc_to_Euro, $date, $conn)
{
        //  Flux : 
        //      $spot = Spot Price for 1 BTC = $spot €
        //      $last_spot = Last $spot
        //      $spot_color = wait ...
        //      $jump_spot = jump -/+
        //      $My_Btc_to_Euro = Use Follow evolution
        //      $date = for date
        //      $conn = for connection DB
        //       

        // Init Color
        $Black = "\33[0;30m";
        $Green = "\33[0;32m";
        $Red = "\33[0;31m";
        $Yellow = "\33[1;33m";
        $White = "\33[1;37m";
        $Light_Green = "\33[1;32m";
        $Light_Red = "\33[1;31m";


        echo "\n  [F] Flux \n";
        // Watch if Spot is Update, elseif yes, Add in DB a Flux Line
        if ($spot != $last_spot) {
            // Add a Line to Flux Table
            $query= "INSERT INTO flux (id, spot, jump_spot, date_time, My_Btc_to_Euro) VALUES (DEFAULT, '$spot', '$jump_spot', '$date', '$My_Btc_to_Euro')";
            $result = $conn->query($query);
             if ($result === TRUE) {      
                echo "\n    [F] (Update DataBase) \n";
                $update = true; 
            } else {
                echo "Error update flux record: \n" . $conn->error;
                die;
            }
        }else{
            $update = false;
        }

        
        
        // Sendmail if Jump of Ratio +/- 15 €
        $message = "  [A] Flux \n
                        [F:Spot] ".$spot." €\n
                        [F:S:Jump] ".$jump_spot." €\n
                        [F:Account:€] ".$My_Btc_to_Euro." €\n
                        [F:Date] ".$date."\n";
                //  Spot Color
                if ($jump_spot > 0) {
                    if ($update == true) {
                        $spot_color = $Light_Green;
                    }else{
                        $spot_color = $Green;
                    }
                    if ($jump_spot > 15) {
                        $object = "Alert: Jump Spot Market jump_spot€ Continue a Jumping !";
                        $this->AlertMail( $object, $message);
                        echo "SendMail for Alert ! \n";
                    }
                }elseif($jump_spot < 0) {
                    if ($update == true) {
                        $spot_color = $Light_Red;
                    }else{
                        $spot_color = $Red;
                    }
                    if ($jump_spot < -15) {
                        $object = "Alert: Spot Market $jump_spot€ Continue a Jumping!";
                        $this->AlertMail( $object, $message);
                        echo "SendMail for Alert ! \n";
                    }
                }

        echo "      [F:Spot] $spot_color".$spot."$White €\n";
        echo "      [F:S:Jump] $spot_color".$jump_spot."$White €\n";
        echo "      [F:Account:€] $Yellow".$My_Btc_to_Euro."$White €\n";
        echo "      [F:Date] $Black".$date."$White\n\n";


        return;
}


function AboutMe($My_Btc, $My_Btc_to_Euro, $start_My_Btc_to_Euro, $date, $conn)
{
        //  AboutMe : 
        //      Return Information to Account.
        //          Variable :
        //              $My_Btc = Btc from account [Saving:DB]
        //              $My_Btc_to_Euro = $My_Btc convert to € [Saving:DB]
        //              $start_My_Btc_to_Euro = $My_Btc_to_Euro when starting (use for diff with $My_Btc_to_Euro) [Saving:DB]
        //              $date = for date [Saving:DB]
        //              $conn = for connection DB

        // Init Color
        $Blue = "\33[0;34m";
        $Yellow = "\33[1;33m";
        $Cyan = "\33[0;36m";
        $White = "\33[1;37m";
        $Green = "\33[0;32m";
        $Red = "\33[0;31m";

        // Calcul Diff for $My_Btc_to_Euro - $start_My_Btc_to_Euro
        $diff = ($My_Btc_to_Euro-$start_My_Btc_to_Euro);
        if($diff < 0){$color=$Red;}elseif($diff > 0){$color=$Green;}else{$color=$White;}

        echo "\n  [A] About \n";
        echo "      [A:WhenStart:€] $Blue".$start_My_Btc_to_Euro."$White €\n";
        echo "      [A:Diff:€]       $color".$diff."$White €\n";
        echo "      [A:Account:€]   $Yellow".$My_Btc_to_Euro."$White €\n";
        echo "      [A:Account:BTC]  $Cyan".$My_Btc."$White BTC\n";

        

        // Set Executed this Order
        $query = "UPDATE account SET My_Btc='$My_Btc', My_Btc_to_Euro='$My_Btc_to_Euro' WHERE id=1";
        // Exécution de la requête
        $result = $conn->query($query);

        // Sendmail if $diff +/- 10
        $message = "  [A] About \n
                             [A:Account:BTC]  $My_Btc BTC \n
                             [A:WhenStart:€] $start_My_Btc_to_Euro € \n
                             [A:Diff:€]       $diff € \n
                             [A:Account:€]   $My_Btc_to_Euro € \n";
            if ($diff > 10) {
                $object = "Alert: Spot Market +10 !";
                $this->AlertMail($object, $message);
                echo "SendMail for Alert ! \n";
            }elseif ($diff < -10) {            
                $object = "Alert: Spot Market -10 !";
                $this->AlertMail( $object, $message);
                echo "SendMail for Alert ! \n";
            }
}

// Base Process
function Process()
{
        // Mail Starting
            $object = "Your Server is Up !";
            $message = "Success Process: ";
            $this->AlertMail($object, $message);
            echo "SendMail for Strating Process ! \n";
        
       // Create connection
            $conn = new mysqli(HOST, USERNAME, PASSWORD, DBNAME);
            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

        // Update Flux
        $this->updatePrices(true);
            $spot = $this->spotPrice;

        // Update Info My Account
        $accounts = $this->client->getAccounts();
            foreach($accounts as $account)
            {
                $MyAccount = $account;
            }


            // Get My BTC to account
            $My_Btc = $MyAccount->getBalance()->getAmount();
            // Get My BTC to Euro
            $My_Btc_to_Euro = ($My_Btc*$spot);
            $start_My_Btc_to_Euro = $My_Btc_to_Euro;

        // Config Loop Process Times
        $counter = 0;
        $while_times = 999999;
    
        // Starting Server Times
        date_default_timezone_set("Europe/Paris"); 
            $start_time = date("H:i:s");

        // Re Init Value
        $spot = null;
        $jump_spot = null;
        $last_jump_spot = null;
        $spot_color = "\33[1;37m";
        
        while ($counter <= $while_times) {
            system('clear');
            $counter++;
            $times = date("H:i:s");
            echo "Loop [$counter] | Start [$start_time] - Now [$times]\n\n";

            // Update Diff
            $last_spot = $spot;
            $last_jump_spot = $jump_spot;
            // Update Price
            $this->updatePrices(true);
            $spot = $this->spotPrice;

            // Update Info My Account
            $accounts = $this->client->getAccounts();
                foreach($accounts as $account)
                {
                    $MyAccount = $account;
                }
                // Get My BTC to account
                $My_Btc = $MyAccount->getBalance()->getAmount();
                // Get My BTC to Euro
                $My_Btc_to_Euro = ($My_Btc*$spot);

            // Spot Config
            //  Spot Diff
            if ($spot == $last_spot) {
                $jump_spot = $last_jump_spot;
            }else{
                $jump_spot = $spot-$last_spot;
            }


            $date = date("Y-m-d H:i:s");

            // Function
            //  AboutMe : 
            //      Return Information to Account.
            //          Variable :
            //              $My_Btc = Btc from account [Saving:DB]
            //              $My_Btc_to_Euro = $My_Btc convert to € [Saving:DB]
            //              $start_My_Btc_to_Euro = $My_Btc_to_Euro when starting (use for diff with $My_Btc_to_Euro) [Saving:DB]
            //              $date = for date [Saving:DB]
            //              $conn = for connection DB
            //
            $about_me = $this->AboutMe($My_Btc, $My_Btc_to_Euro, $start_My_Btc_to_Euro, $date, $conn);

            //  Flux : 
            //      Return & Save in the DB the new data of Btc Market
            //      $spot = Spot Price for 1 BTC = $spot €
            //      $last_spot = Last $spot
            //      $spot_color = wait ...
            //      $jump_spot = jump -/+
            //      $My_Btc_to_Euro = Use Follow evolution
            //      $date = for date
            //      $conn = for connection DB
            //
            $flux = $this->Flux($spot, $last_spot, $spot_color, $jump_spot, $My_Btc_to_Euro, $date, $conn);

            //  Order : 
            //      Check if Order exist & Check if Spot Target is near for Buy/Sell
            //      $spot = Spot Price for 1 BTC = $spot €
            //      $My_Btc = 
            //      $My_Btc_to_Euro = Follow evolution
            //      $date = for date
            //      $conn = for connection DB
            //
            $initialize = $this->Initialize($spot, $My_Btc, $My_Btc_to_Euro, $date, $conn);
           
            //  History : 
            //      $conn = for connection DB
            //
            $history = $this->History($conn);


            sleep(SLEEPTIME);
        }

}





    function checkBalanceOfAccount($account)
    {
        $data = $account->getBalance();
        $amount = $data->getAmount();
        $currency = $data->getCurrency();

        return $amount;
    }


    function updatePrices($silent=false)
    {
        $this->lastSellPrice = $this->sellPrice;
        $this->buyPrice =  floatval($this->client->getBuyPrice(CRYPTO.'-'.CURRENCY)->getAmount());
        $this->sellPrice = floatval($this->client->getSellPrice(CRYPTO.'-'.CURRENCY)->getAmount());
        $this->spotPrice = floatval($this->client->getSpotPrice(CRYPTO.'-'.CURRENCY)->getAmount());

        if(!$this->lastSellPrice)
            $this->lastSellPrice = $this->sellPrice;

        if($silent===false)
        {
            // echo "[i] Buy price:\033[0;34m $this->buyPrice \033[0m".CURRENCY."\n";
            // echo "[i] Sell price:\033[0;34m $this->sellPrice \033[0m".CURRENCY."\n";
            echo "[i] Spot price:\033[0;32m $this->spotPrice \033[0m".CURRENCY."\n";
            echo "[i] Difference buy/sell:\033[0;34m ".round(abs($this->buyPrice-$this->sellPrice),2)." \033[0m".CURRENCY."\n\n";
        }
    }


    /*
    * Buys the configured crypto for real money
    * $money is $ or €, not some other crypto
    */
    function buyCryptoInMoney($money)
    {

        echo " [B] Buying $money ".CURRENCY.' of '.CRYPTO."\n";
        $buy = new Buy([
            'amount' => new Money($money, CURRENCY),
            'paymentMethodId' => $this->wallet->getId()
        ]);
            $this->client->createAccountBuy($this->account, $buy);
    }

    function buyBTC($amount,$btc=false)
    {
        $eur = ($btc===true?($this->buyPrice*$amount):$amount);
        $btc = ($btc===true?$amount:($amount/$this->buyPrice));
        
            $buy = new Buy([
                'amount' => new Money($btc, CRYPTO),
                'paymentMethodId' => $this->wallet->getId()
            ]);
                 $this->client->createAccountBuy($this->account, $buy);

            echo "[B #$id] Buying $eur €\t=\t$btc ".CRYPTO."\n";

        return $id;
    }


    function sellBTC($amount,$btc=false)
    {
        $eur = ($btc===true?($this->sellPrice*$amount):$amount);
        $btc = ($btc===true?$amount:($amount/$this->sellPrice));
        $sell = new Sell([
            'bitcoinAmount' => $btc,
            'amount' => new Money($btc, CRYPTO)
        ]);
        
            echo "[Sell] Selling $eur € =\t$btc ".CRYPTO."\n";
       
            if($this->checkBalanceOfAccount($this->account)<$btc)
            {
                echo " [ERR] You don't have enough ".CRYPTO." in your '".$this->account->getName()."'. Cancelling sell\n";
                return;
            }
            else
                $this->client->createAccountSell($this->account, $sell);            
        
    }


}









