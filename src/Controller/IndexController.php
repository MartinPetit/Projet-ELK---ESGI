<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Abraham\TwitterOAuth\TwitterOAuth;

class IndexController extends AbstractController
{
    /**
     * @Route("/index", name="index")
     */
    public function index(): Response
    {
        $oauth = new TwitterOAuth('Xne1QN8yFTdFVuuM2spk8j1PJ', 'PbnxbjUpAJSczecdJsgVmMvvyQG9ISi24F1OjIOteNmkc1MYjF');
        $accessToken = $oauth->oauth2('oauth2/token', ['grant_type' => 'client_credentials']);
        $access_token = $accessToken->access_token;


        $connection = new TwitterOAuth('VrZO81nW8xajx2m2SCKoWDZvn', 'j2wyHisxIv1h555A7KE8q259DObTUtcflRVpgGdwtPRHJZRbqx', null, $access_token);
        $connection->setTimeouts(150, 150);

        $maxId     = 0;
        $allTweets = [];
        $i         = 0;

        $statuses = $this->getTweets($connection, $maxId);
        array_push($allTweets, $statuses->statuses);
        $tweets    = $statuses->statuses;

        dd($allTweets[0][0]->user);

        while (sizeof($tweets) > 0) {
            $statuses = $this->getTweets($connection, $maxId);
            $tweets    = $statuses->statuses;
            if (sizeof($tweets) > 0) {
                array_push($allTweets, $statuses->statuses);
                $maxId     = end($tweets)->id - 1;
            }
            $i++;
        }




        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
        ]);
    }

    public function getTweets($connection, $maxId) {
        return $connection->get("search/tweets", ["q" => "%23télétravail+OR+%23teletravail+OR+%23homeoffice+OR+%23cours+OR+%23distanciel+OR+%23lycée+OR+%23étudiant+OR+%23distance+OR+%23zoom+OR+%23teams", "max_id" => $maxId, "lang" => "fr", "count" => "100", 'tweet_mode' => 'extended']);
    }
}
