<?php


namespace App\Command;


use Abraham\TwitterOAuth\TwitterOAuth;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InsertDataCommand extends Command
{
    protected static $defaultName = 'import:data';

    private $em;
    private $container;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container
    ) {
        parent::__construct();
        $this->em = $em;
        $this->container = $container;
    }

    protected function configure()
    {
        $this->setDescription('Importation des données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Waiting for data insert');
        $client = new Client();

        $oauth = new TwitterOAuth('Xne1QN8yFTdFVuuM2spk8j1PJ', 'PbnxbjUpAJSczecdJsgVmMvvyQG9ISi24F1OjIOteNmkc1MYjF');
        $accessToken = $oauth->oauth2('oauth2/token', ['grant_type' => 'client_credentials']);
        $access_token = $accessToken->access_token;


        $connection = new TwitterOAuth('VrZO81nW8xajx2m2SCKoWDZvn', 'j2wyHisxIv1h555A7KE8q259DObTUtcflRVpgGdwtPRHJZRbqx', null, $access_token);

        $maxId     = 0;
        $allTweets = [];

        $statuses = $this->getTweets($connection, $maxId);

        array_push($allTweets, $statuses->statuses);
        $tweets    = $statuses->statuses;

        while (sizeof($tweets) > 0) {
            $statuses = $this->getTweets($connection, $maxId);
            $tweets    = $statuses->statuses;
            if (sizeof($tweets) > 0) {
                array_push($allTweets, $statuses->statuses);
                $maxId     = end($tweets)->id - 1;
            }
        }

        for ($i = 0; $i < sizeof($allTweets); $i++) {
            $k = 1;
            foreach ($allTweets[$i] as $data) {
                $io->progressStart($k);

                $client->request(
                    'POST',
                    'http://localhost:9200/projet-elk/_doc',
                    [
                        'json' => [
                            'text' => $data->full_text,
                            'idTwitt' => $data->id,
                            'userName'=> $data->user->name

                        ]
                    ]
                );
                $k++;
            }
        }
        $io->progressAdvance();
        $io->success('Import terminated');
        $io->progressFinish();
        return 0;
    }

    public function getTweets($connection, $maxId) {
        return $connection->get("search/tweets", ["q" => "%23cours+OR+%23distanciel+OR+%23lycée+OR+%23étudiant+OR+%23distance+OR+%23zoom+OR+%23teams", "max_id" => $maxId, "count" => "100", 'lang' => 'fr', 'tweet_mode' => 'extended']);
    }
}