<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class OPSGame implements MessageComponentInterface
{
    const _SELF     = 'self';
    const _OTHERS   = 'others';
    const _EVERYONE = '*';

    public $db;
    public $theme;
    public $addons;
    public $rooms;
    public $activeAddons;

    public $gameID = false;
    public $player = false;
    public $seat   = false;
    public $game   = false;

    public $games = [];
    public $players = [];

    public function __construct()
    {
        global $pdo, $addons, $opsTheme;

        $this->db           = $pdo;
        $this->theme        = $opsTheme;
        $this->addons       = $addons;
        $this->rooms        = [];
        $this->activeAddons = \OPSAddon::getActives();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        parse_str($conn->httpRequest->getUri()->getQuery(), $query);

        if ( !isset($query['gamepass'], $query['player']) ) {
            $conn->close();
            return;
        }

        $gamepass = $query['gamepass'];
        $player   = $query['player'];
        $pdata    = $this->playerdata(['vID', 'gamepass'], $player);
        $gameID   = $pdata->vID;

        if ($gamepass !== $pdata->gamepass) {
            $conn->close();
            return;
        }

        if ( !isset($this->rooms[ $gameID ]) )
            $this->rooms[ $gameID ] = new \SplObjectStorage;
        
        $this->rooms[ $gameID ]->attach($conn);
        //echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $json = json_decode($msg);
        $res  = $this->load($json);
        $room = false;

        if ( !$res )
            return;

        foreach ( $this->rooms as $rom ):
            if ( !$rom->contains($from) ):
                continue;
            endif;

            $room = $rom;
        endforeach;

        if ( !$room )
            return;

        foreach ( $room as $client ):
            if (
                ($json->to === self::_SELF
                && $from->resourceId != $client->resourceId)
                || ($json->to === self::_OTHERS
                && $from->resourceId == $client->resourceId)
            )
                continue;
            
            $client->send( $this->response($res) );
        endforeach;
    }

    public function onClose(ConnectionInterface $conn)
    {
        $room = false;

        foreach ( $this->rooms as $rom ):
            if ( !$rom->contains($conn) ):
                continue;
            endif;

            $room = $rom;
        endforeach;

        if ( !$room )
            return;

        $room->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

    /** */
    public function response($res)
    {
        if ( !$res )
        {
            return json_encode([
                'type' => 'none'
            ]);
        }

        return json_encode($res);
    }

    public function load($data)
    {
        $this->activeAddons = \OPSAddon::getActives();
        $this->updateDependencies($data);

        if ( !$player = &$this->getDependencies($data) )
            return ['type' => 'bad-deps'];
        
        if ( !in_array($data->type, ['alone', 'join', 'enter', 'leave', 'debug']) && $this->isAlone($data, $player) )
            return ['type' => 'is-alone'];

        $this->playerdata([
            'timetag' => time(),
        ], $player);

        switch ($data->type):
            case 'debug':
                return $this->players;
                break;

            case 'chat':
                return $this->chat($data, $player);
                break;
            
            case 'self-chat':
                return $this->chat($data, $player, true);
                break;
            
            case 'proctor':
                return $this->proctor($data, $player);
                break;
            
            case 'enter':
                return $this->enter($data, $player);
                break;
            
            case 'join':
                return $this->join($data, $player);
                break;
            
            case 'leave':
                return $this->leave($data, $player);
                break;
            
            case 'start':
                return $this->startGame($data, $player);
                break;
            
            case 'assign-dealer':
                return $this->assignDealer($data, $player);
                break;
            
            case 'assign-chipleader':
                return $this->assignChipleader($data, $player);
                break;
            
            case 'post-small-blinds':
                return $this->postSmallBlinds($data, $player);
                break;
            
            case 'post-big-blinds':
                return $this->postBigBlinds($data, $player);
                break;
            
            case 'deal-cards':
                return $this->dealCards($data, $player);
                break;
            
            case 'get-my-cards':
                return $this->getMyCards($data, $player);
                break;

            case 'get-my-turn':
                return $this->getMyTurn($data, $player);
                break;
            
            case 'start-timer':
                return $this->startTimer($data, $player);
                break;
            
            case 'player-action':
                return $this->playerAction($data, $player);
                break;
            
            case 'showdown':
                return $this->showdown($data, $player);
                break;
            
            case 'finish':
                return $this->finish($data, $player);
                break;
            
            case 'restart':
                return $this->restart($data, $player);
                break;
            
            case 'alone':
                return $this->alone($data, $player);
                break;
            
            default:
                return false;
                break;
        endswitch;
    }

    public function isAlone($data, $player)
    {
        // if ( $player->game->hand < 0 )
        //     return false;
        
        $x = 0;

        $np   = $this->getNumberOfPlayers($player);
        $bu   = $this->getBustedPlayers($player);
        $nbpl = $np - $bu;

        return $nbpl < 2 ? true : false;
    }

    public function alone($data, $player)
    {
        // if ( $player->game->hand < 0 )
        //     return false;
        
        $alone = false;
        $x     = 0;

        for ($i = 1; $i < 11; $i++):
            $plyr = $player->game->{"p{$i}name"};

            if (empty($plyr) || !isset($this->players[$plyr])):
                continue;
            endif;

            $seatPlayer = $this->players[$plyr];
            $isBusted   = $this->getBustedPlayers($player, $i);

            if ( $isBusted )
            {
                $this->kickPlayer($seatPlayer);
                continue;
            }

            $x++;
            $alone = $seatPlayer;
        endfor;

        $cards = [];
        for ($i = 1; $i < 11; $i++) {
            for ($j = 1; $j <= ($player->game->gamestyle === 'o' ? 4 : 2); $j++) {
                $cards["p{$i}card{$j}"] = '';
            }
        }

        $gameData = [
            'pot'      => 0,
            'bet'      => 0,
            'dealer'   => 0,
            'move'     => 0,
            'lastbet'  => 0,
            'hand'     => -1,
            'lastmove' => time(),
        ];

        if ($x === 1):
            $gameData["p{$alone->seat}pot"] = $alone->game->pot + intval($alone->pot);
            $gameData["p{$alone->seat}bet"] = 0;
            $gameData['dealer']             = $alone->seat;
        endif;

        $this->gamedata(array_merge($gameData, $cards), $player);
        return [
            'type'    => 'alone',
            'log'     => $this->log('', GAME_MSG_PLAYERS_JOINING, $player),
            'players' => $this->getPlayersData($player),
            'table'   => $this->getTableData($player),
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function chat($data, $player, $self = false)
    {
        $this->theme->addVariable('chatter', array(
            'name'   => $player->id,
            'avatar' => get_ava($player->id),
        ));
        $this->theme->addVariable('message', strval($data->message));

        $log = $self ? $this->theme->viewPart('poker-chat-message-me') : $this->theme->viewPart('poker-chat-message-other');

        return [
            'type'    => 'chat',
            'message' => $log,
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function proctor($data, $player)
    {
        $player->lastAct = time();

        $isInterplay   = $this->isInterplay($player);
        $isDealer      = $player->seat === $player->game->dealer;
        $actOnBehalf   = false;
        $dealerChanged = false;

        if ( $isDealer ):
            if ( !$isInterplay ):
                return false;
            endif;

            $diff = time() - $player->game->lastmove;

            if ($diff > MOVETIMER):
                $actOnBehalf = true;
            endif;
        else:
            if ($player->game->hand < 0):
                return false;
            endif;

            $diff = time() - $player->game->lastmove;
            $skip = $isInterplay ? MOVETIMER + 10 : 10;

            if ($diff > $skip):
                $nextDealer = $this->nextDealer($player);

                if ($nextDealer > 0):
                    $this->gamedata([
                        'dealer' => $nextDealer,
                    ], $player);
                    $dealerChanged = true;
                endif;
            endif;
        endif;

        if (!$actOnBehalf && !$dealerChanged):
            return false;
        endif;

        return [
            'type'          => 'proctor',
            'table'         => $this->getTableData($player),
            'player'        => $this->getPlayerData($player),
            'actOnBehalf'   => $actOnBehalf,
            'dealerChanged' => $dealerChanged,
            'dom'           => $this->getDomChanges($player),
        ];
    }

    public function enter($data, $player)
    {
        return [
            'type'    => 'enter',
            'players' => $this->getPlayersData($player),
            'table'   => $this->getTableData($player),
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function join($data, $player)
    {
        if ($data->seat < 1 || $data->seat > 10)
            return ['type' => 'bad-seat'];
        
        if ( $player->game->{'p' . $data->seat . 'name'} !== '' )
        {
            $sname      = $player->game->{'p' . $data->seat . 'name'};
            $seatPlayer = isset($this->players[ $sname ]) ? $this->players[ $sname ] : false;
            $isPlayer   = boolval($this->playerdata(['ID'], $seatPlayer ? $seatPlayer : $sname));

            if ( $seatPlayer && $isPlayer )
                return ['type' => 'seat-not-empty'];
            elseif ( $seatPlayer && !$isPlayer )
                $this->kickPlayer($seatPlayer);
        }

        for ($i = 1; $i < 11; $i++):
            if ($player->game->{'p' . $i . 'name'} !== $player->id)
                continue;

            return ['type' => 'already-seated'];
            break;
        endfor;

        $statpotfield = $this->addons->get_hooks(
            array(
                'content' => 'winpot',
                'table'   => $player->game,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'stat_pot_field'
            )
        );

        $winnings = $this->statdata([ $statpotfield ], $player);
        $amount   = isset($data->amount) ? transfer_to(intval($data->amount)) : $winnings;
        $amount   = $amount > $winnings ? $winnings : $amount;
        $proceed  = $this->addons->get_hooks(
            array(
                'content' => $player->game->tabletype == 't' && $player->game->hand >= 0 ? false : true,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'wss',
                'location' => 'proceed_bool'
            )
        );

        if ($proceed)
        {
            $chips = intval($this->addons->get_hooks(
                array(
                    'content' => $player->isBot ? $amount : ($amount > intval($player->game->tablelimit) ? $player->game->tablelimit : $amount),
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'chips_variable'
                )
            ));

            $cost = $this->addons->get_hooks(
                array(
                    'content' => $chips,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'cost_variable'
                )
            );

            if ($player->game->tabletype == 't')
                $player->game->tablelow = intval($player->game->tablelimit);

            $player->game->tablelow = $this->addons->get_hooks(
                array(
                    'content' => $player->game->tablelow,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'tablelow_variable'
                )
            );
            
            if ($chips >= $player->game->tablelow && $chips > 0)
            {
                /**/
                $np = 0;
                $ai = 0;
                $fo = 0;
                $bu = 0;

                for ($i = 1; $i < 11; $i++)
                {
                    $usr   = $player->game->{'p' . $i . 'name'};
                    $upot  = $player->game->{'p' . $i . 'pot'};
                    $ubet  = $player->game->{'p' . $i . 'bet'};
                    $ufold = substr($ubet, 0, 1);

                    if (empty($usr))
                        continue;

                    $np++;

                    if ($upot == 0 && $ubet > 0 && $ufold != 'F' && ($player->game->hand > 4 && $player->game->hand < 15))
                        $ai++;

                    if ($ufold == 'F' && $upot > 0 && ($player->game->hand > 4 && $player->game->hand < 15))
                        $fo++;

                    if (($ubet == 0 || $ufold == 'F') && $upot == 0)
                        $bu++;
                }

                $allpl           = $np - $bu;
                $blindmultiplier = ($player->game->tabletype === 't') ? (11 - $allpl) : 4;
                switch (intval($player->game->tablelimit))
                {
                    case 25000:
                    $tablemultiplier = 2;
                        break;

                    case 50000:
                    $tablemultiplier = 4;
                        break;

                    case 100000:
                    $tablemultiplier = 8;
                        break;

                    case 250000:
                    $tablemultiplier = 20;
                        break;

                    case 500000:
                    $tablemultiplier = 40;
                        break;

                    case 1000000:
                    $tablemultiplier = 80;
                        break;

                    default:
                    $tablemultiplier = 1;
                        break;
                }

                list($smallBlinds, $bigBlinds) = $this->getBlinds($player);

                $joinBet = (PLAYBFRCARDS === 'yes' && $player->game->hand > 0 && $player->game->hand <= 4) ? $bigBlinds : 'F';

                /**/
                $this->gamedata([
                    "p{$data->seat}name" => $player->id,
                    "p{$data->seat}bet"  => $joinBet,
                    "p{$data->seat}pot"  => $chips,
                ], $player);

                $player->seat    = $data->seat;
                $player->lastAct = time();
                $player->pot     = &$player->game->{"p{$data->seat}pot"};
                $player->bet     = &$player->game->{"p{$data->seat}bet"};
                $player->card1   = &$player->game->{"p{$data->seat}card1"};
                $player->card2   = &$player->game->{"p{$data->seat}card2"};

                if ($player->game->gamestyle === 'o'):
                    $player->card3 = &$player->game->{"p{$data->seat}card3"};
                    $player->card4 = &$player->game->{"p{$data->seat}card4"};
                endif;

                $player->cards = $player->game->gamestyle === 'o' ? [&$player->card1, &$player->card2, &$player->card3, &$player->card4] : [&$player->card1, &$player->card2];

                $player->startpot  = $chips;
                $player->lastchips = $chips;
                $player->chipswon  = 0;

                $bank = $winnings - $cost;

                $sitelog  = sprintf( __( '%s joined table %s on seat %s with %s pot', 'core' ), $player->id, $player->game->tablename, $player->seat, money($chips) );

                if ($cost !== $chips)
                    $sitelog = sprintf( __( '%s joined table %s on seat %s with %s pot which cost him %s', 'core' ), $player->id, $player->game->tablename, $player->seat, money($chips), money($cost, true, $this->moneyPrefix($player)) );

                $sitelog .= sprintf( __( ", he has %s left in his bank", 'core' ), money($bank, true, $this->moneyPrefix($player)) );

                OPS_sitelog($player->id, $sitelog);

                if ($player->game->tabletype == 't')
                {
                    $this->statdata([
                        'tournamentsplayed' => '::>tournamentsplayed + 1',
                        $statpotfield => $bank
                    ], $player);
                }
                else
                {
                    $this->statdata([
                        'gamesplayed' => '::>gamesplayed + 1',
                        $statpotfield => $bank,
                    ], $player);
                }

                $this->playerdata([
                    'gID'      => $player->game->gameID,
                    'lastmove' => time(),
                ], $player);

                $this->addons->get_hooks(
                    [
                        'wss'     => $this,
                        'player'  => $player,
                        'request' => $data
                    ],
                    [
                        'page'     => 'wss',
                        'location' => 'player_joined'
                    ]
                );

                $this->theme->addVariable('player', array(
                    'name'   	  => $player->id,
                    'pot'    	  => money($chips),
                    'avatar' 	  => get_ava($player->id),
                    'before_name' => $this->addons->get_hooks(
                        array(
                            'player' => $player->id,
                            'wss'    => $this,
                            'player' => $player,
                        ),
                        array(
                            'page'     => 'wss',
                            'location' => 'player_before_name'
                        )
                    )
                ));

                return [
                    'type'    => 'join',
                    'log'     => $this->log($player, GAME_PLAYER_BUYS_IN . ' ' . money($chips, true, $this->moneyPrefix($player))),
                    'dom'     => $this->getDomChanges($player),
                    'table'   => $this->getTableData($player),
                    'players' => $this->getPlayersData($player),
                    'player'  => $this->getPlayerData($player),
                ];
            } else {
                if ( $player->game->tabletype == 't') {
                    return ['type' => 'bal-1'];
                } else {
                    return ['type' => 'bal-2'];
                }
            }
        }
        else
            return ['type' => 'bal-3'];
        
        $this->gamedata([
            'lastmove' => time()
        ], $player);
    }

    public function leave($data, $player)
    {
        $isPlaying = empty($player->seat) ? false : true;

        if ( !$isPlaying ):
            $playerId = $player->id;
            $this->kickPlayer($player);
            unset( $this->players[ $playerId ] );

            return ['type' => 'exit', 'player' => $playerId];
        endif;

        $leave = $this->addons->get_hooks(
            array(
                'content' => $player->game->hand < 3 ? true : false,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'includes/inc_poker.php',
                'location' => 'leave_bool',
            )
        );

        if ( !$leave )
            return ['type' => 'leave-after-finish', 'player' => ['seat' => $player->seat, 'id' => $player->id]];

        // $exitTable = empty($player->seat) ? true : false;
        // $exitTable = $this->addons->get_hooks(
        //     array(
        //         'content' => $exitTable,
        //         'wss'     => $this,
        //         'player'  => $player,
        //     ),
        //     array(
        //         'page'     => 'includes/inc_poker.php',
        //         'location' => 'exit_table_bool',
        //     )
        // );

        // if ( !$exitTable )
        //     return false;
        
        $seat = $player->seat;
        $this->kickPlayer($player);

        $this->addons->get_hooks(
            array(
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'      => 'includes/inc_poker.php',
                'location'  => 'if_player_exists',
            )
        );

        $this->playerdata([
            'waitimer' => time() + intval(WAITIMER),
            'gID'      => 0,
        ], $player);
        $this->theme->addVariable('seat_number', $seat);

        return [
            'type'   => 'leave',
            'log'    => $this->log($player, __( 'left the table', 'core' )),
            'player' => [
                'seat'   => $seat,
                'name'   => '',
                'pot'    => '',
                'bet'    => '',
                'avatar' => $this->theme->viewPart('poker-player-circle'),
                'info'   => '',
                'cards'  => [],
            ],
            'dom'    => $this->getDomChanges($player),
        ];
    }

    public function startGame($data, $player)
    {
        if ($player->game->hand !== -1):
            return ['type' => 'cannot-start', 'reason' => 'not-idle'];
        endif;

        if ( !isset($player->seat) || !$player->seat )
            return ['type' => 'not-seated'];
        
        $this->gamedata(
            [
                'hand'   => 0,
                'pot'    => 0,
                'bet'    => 0,
                'move'   => $player->seat,
                'dealer' => $player->seat
            ],
            $player,
            // [
            //     'page'     => 'includes/player_move.php',
            //     'location' => 'start_sql1'
            // ]
        );

        $this->playerdata(['lastmove' => time()], $player);
        $this->addons->get_hooks(
            array(
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'includes/player_move.php',
                'location' => 'game_start'
            )
        );

        $player->lastAct = time();

        return [
            'type'  => 'start',
            'log'   => $this->log('', GAME_STARTING, $player),
            'table' => $this->getTableData($player),
            'dom'   => $this->getDomChanges($player),
        ];
    }

    public function restart($data, $player)
    {
        if ($player->seat !== $player->game->dealer)
            return ['type' => 'bad-dealer'];

        if ($player->game->hand !== 0):
            return ['type' => 'cannot-restart', 'reason' => 'bad-hand'];
        endif;

        $opsTheme   = $this->theme;
        $game_style = $player->game->gamestyle;
        require __DIR__ . '/includes/card_sys.php';

        $numOfCards  = $player->game->gamestyle === 'o' ? 4 : 2;
        $playerCards = [];
        $busts       = [];

        /** */
        if ($player->nofold_handsplayed > $this->statdata(['nofold_handsplayed'], $player)):
            $this->statdata([
                'nofold_handsplayed' => $player->nofold_handsplayed
            ], $player);
        endif;

        if ($player->noleave_handsplayed > $this->statdata(['noleave_handsplayed'], $player)):
            $this->statdata([
                'noleave_handsplayed' => $player->noleave_handsplayed
            ], $player);
        endif;

        for ($i = 1; $i < 11; $i++):
            for ($j = 1; $j <= $numOfCards; $j++):
                $playerCards["p{$i}card{$j}"] = '';
            endfor;

            $plyr = $player->game->{"p{$i}name"};

            if ( !isset($this->players[ $plyr ]) || $this->players[ $plyr ]->seat === false )
                continue;
            
            $seatPlayer = &$this->players[ $plyr ];

            if ( empty($plyr) || !boolval($this->playerdata(['ID'], $seatPlayer)) ):
                $this->kickPlayer($seatPlayer);
            endif;

            $seatPlayer->allin_this_hand     = false;
            $seatPlayer->nofold_handsplayed  = 0;
            $seatPlayer->noleave_handsplayed = 0;

            $ratio      = floatval(number_format(intval($seatPlayer->pot) / (isset($seatPlayer->startpot) && $seatPlayer->startpot > 0 ? $seatPlayer->startpot : 1), 2));

            if (intval($seatPlayer->pot) > $seatPlayer->lastchips)
            {
                $seatPlayer->chipswon = $seatPlayer->chipswon + ($seatPlayer->pot - $seatPlayer->lastchips);
                $earnedChips = $seatPlayer->pot - $seatPlayer->lastchips;

                if ($seatPlayer->went_allin && $earnedChips > $this->statdata(['max_allin_chipswon'], $seatPlayer))
                {
                    $this->statdata([
                        'max_allin_chipswon' => $earnedChips
                    ], $seatPlayer);

                    $seatPlayer->went_allin = false;
                }
            }
            elseif (intval($seatPlayer->pot) < $seatPlayer->lastchips)
                $seatPlayer->chipswon = $seatPlayer->chipswon - (intval($seatPlayer->lastchips) - intval($seatPlayer->pot));
            
            if ($seatPlayer->chipswon > $this->statdata(['max_chipswon'], $seatPlayer))
                $this->statdata(['max_chipswon' => $seatPlayer->chipswon], $seatPlayer);
                        
            if ($ratio > $this->statdata(['max_multiplypotratio'], $seatPlayer))
                $this->statdata(['max_multiplypotratio' => $ratio], $seatPlayer);

            $seatPlayer->lastchips = intval($seatPlayer->pot);
            
            if ( !$seatPlayer->isBot && isset($seatPlayer->lastAct) && $seatPlayer->lastAct > 0 && time() - $seatPlayer->lastAct > 150):
                $this->kickPlayer($seatPlayer);
            endif;
        endfor;

        /** */

        $nextPlayer = $this->nextPlayer($player);

        $this->gamedata(
            array_merge(
                [
                    'pot'     => 0,
                    'bet'     => 0,
                    'lastbet' => 0,
                    'card1'   => '', 'card2'   => '', 'card3'   => '',
                    'card4'   => '', 'card5'   => '',
                    'p1bet'   => 0, 'p2bet'    => 0, 'p3bet'    => 0, 'p4bet'    => 0,
                    'p5bet'   => 0, 'p6bet'    => 0, 'p7bet'    => 0, 'p8bet'    => 0,
                    'p9bet'   => 0, 'p10bet'   => 0
                ],
                $playerCards
            ),
            $player
        );

        $this->addons->get_hooks([
            'wss'    => $this,
            'player' => $player,
        ], [
            'page'     => 'wss',
            'location' => 'on_restart'
        ]
        );
        
        $player->lastAct = time();
        return [
            'type'    => 'restart',
            'table'   => $this->getTableData($player),
            'players' => $this->getPlayersData($player),
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function assignDealer($data, $player)
    {
        // Next Dealer Seat
        $nds = $this->nextDealer($player);

        if ($nds < 1 || $nds > 10)
            return false;

        $dealerName = $player->game->{"p{$nds}name"};

        if (empty($dealerName) || !isset($this->players[$dealerName]) || $this->players[$dealerName]->seat === false)
            return false;

        $dealer = $this->players[$dealerName];

        $this->gamedata([
            'lastmove' => time(),
            'dealer'   => $dealer->seat,
            'move'     => $dealer->seat,
            'hand'     => 1,
        ], $player, [
            'page'     => 'includes/auto_move.php',
            'location' => 'hand0_sql'
        ]);

        $this->addons->get_hooks(
            [
                'wss'    => $this,
                'player' => $player,
            ],
            [
                'page'     => 'wss',
                'location' => 'ante'
            ]
        );

        $player->lastAct = time();
        return [
            'type'  => 'assign-dealer',
            'log'   => $this->log($dealer, GAME_MSG_DEALER_BUTTON),
            'table' => $this->getTableData($player),
            'dom'   => $this->getDomChanges($player),
        ];
    }

    public function assignChipleader($data, $player)
    {
        if ($this->isStraddle($player)):
            if (($player->game->lastmove + STRADDLETIMR) > time()):
                return false;
            endif;
        endif;

        if ($player->seat !== $player->game->dealer)
            return false;

        $i     = 1;
        $chips = 0;
        $err   = true;

        while ($i < 11)
        {
            $pl = $player->game->{"p{$i}name"};
            $i++;

            if (empty($pl) || !isset($this->players[ $pl ])):
                continue;
            endif;

            $seatplayer = &$this->players[ $pl ];

            if (intval($seatplayer->pot) > $chips):
                $chips      = intval($seatplayer->pot);
                $chipleader = $seatplayer->id;
                $err        = false;
            elseif (intval($seatplayer->pot) == $chips):
                $err = true;
            endif;

            $this->statdata(['handsplayed' => '::>handsplayed + 1'], $seatplayer);
        }

        $nextPlayer = $this->nextPlayer($player);

        $this->gamedata([
            'move'     => $nextPlayer,
            'hand'     => 2,
            'lastmove' => time(),
        ], $player);

        $player->lastAct = time();
        return [
            'type'       => 'assign-chipleader',
            'log'        => $this->log(
                $err ? '' : $seatplayer,
                $err ? GAME_MSG_LETS_GO : GAME_MSG_CHIP_LEADER,
                $err ? $seatplayer : false
            ),
            'table' => $this->getTableData($player),
            'dom'   => $this->getDomChanges($player),
        ];
    }

    public function postSmallBlinds($data, $player)
    {
        if ($player->seat !== $player->game->dealer)
            return false;
        
        list($smallBlinds, $bigBlinds) = $this->getBlinds($player);

        $plyr = $player->game->{"p{$player->game->move}name"};

        if (empty($plyr) || !isset($this->players[$plyr]) || $this->players[$plyr] === false):
            return false;
        endif;

        $mover      = $this->players[$plyr];
        $nextPlayer = $this->nextPlayer($player, $mover->seat);

        if (intval($mover->pot) > $smallBlinds)
        {
            $npot = intval($mover->pot) - $smallBlinds;
            $nbet = $smallBlinds;
            $log  = $this->log($mover, GAME_MSG_SMALL_BLIND . ' ' . money_small($smallBlinds, true, $this->moneyPrefix($mover)));
        }
        else
        {
            $npot = 0;
            $nbet = $smallBlinds;
            $log  = $this->log($mover, GAME_PLAYER_GOES_ALLIN);
        }

        $this->gamedata([
            'pot'                => intval($player->game->pot) + $nbet,
            'move'               => $nextPlayer,
            "p{$mover->seat}pot" => $npot,
            "p{$mover->seat}bet" => $nbet,
            'hand'               => 3,
            'lastmove'           => time(),
        ], $player);

        $this->theme->addVariable('player', array(
			'name'   	  => $mover->id,
			'pot'    	  => money($mover->pot, true, $this->moneyPrefix($mover)),
			'avatar' 	  => get_ava($mover->id),
			'before_name' => $this->addons->get_hooks(
				array(
					'player' => $mover->id,
                    'wss'    => $this,
                    'player' => $player,
				),
				array(
					'page'     => 'wss',
					'location' => 'player_before_name'
				)
			)
		));
        $this->theme->addVariable('bet_money', money_small($nbet, true, $this->moneyPrefix($mover)));

        $this->addons->get_hooks(
            [
                'wss'    => $this,
                'player' => $player,
            ],
            [
                'page'     => 'wss',
                'location' => 'hand_change'
            ]
        );

        $player->lastAct = time();
        return [
            'type'   => 'post-small-blinds',
            'log'    => $log,
            'table'  => $this->getTableData($player),
            'player' => [
                'seat' => $mover->seat,
                'info' => $this->theme->viewPart('poker-player-info'),
                'bet'  => $this->theme->viewPart('poker-player-chips'),
            ],
            'next'   => [
                'seat' => $nextPlayer
            ],
            'dom'    => $this->getDomChanges($player),
        ];
        //hand_hook();
    }

    public function postBigBlinds($data, $player)
    {
        if ($player->seat !== $player->game->dealer)
            return false;
        
        list($smallBlinds, $bigBlinds) = $this->getBlinds($player);

        $plyr = $player->game->{"p{$player->game->move}name"};

        if (empty($plyr) || !isset($this->players[$plyr]) || $this->players[$plyr] === false):
            return false;
        endif;

        $mover      = $this->players[$plyr];
        $nextPlayer = $this->nextPlayer($player, $mover->seat);

        if (intval($mover->pot) > $bigBlinds)
        {
            $npot  = intval($mover->pot) - $bigBlinds;
            $nbet  = $bigBlinds;
            # $lbet  = $autoplayer . '|' . $bigBlinds;
            $log   = $this->log($mover, GAME_MSG_BIG_BLIND . ' ' . money_small($bigBlinds, true, $this->moneyPrefix($mover)));
        }
        else
        {
            $npot  = 0;
            $nbet  = $bigBlinds;
            # $lbet  = '';
            $log   = $this->log($mover, GAME_PLAYER_GOES_ALLIN);
        }
        
        $this->gamedata([
            'pot'                 => $mover->game->pot + $nbet,
            'bet'                 => $nbet,
            'move'                => $nextPlayer,
            "p{$mover->seat}pot" => $npot,
            "p{$mover->seat}bet" => $nbet,
            'hand'                => 4,
            'lastmove'            => time(),
        ], $player);

        $this->theme->addVariable('player', array(
			'name'   	  => $mover->id,
			'pot'    	  => money($mover->pot, true, $this->moneyPrefix($mover)),
			'avatar' 	  => get_ava($mover->id),
			'before_name' => $this->addons->get_hooks(
				array(
					'player' => $mover->id,
                    'wss'    => $this,
                    'player' => $player,
				),
				array(
					'page'     => 'wss',
					'location' => 'player_before_name'
				)
			)
		));
        $this->theme->addVariable('bet_money', money_small($nbet, true, $this->moneyPrefix($mover)));

        $player->lastAct = time();
        return [
            'type'   => 'post-big-blinds',
            'log'    => $log,
            'table'  => $this->getTableData($player),
            'player' => [
                'seat' => $mover->seat,
                'info' => $this->theme->viewPart('poker-player-info'),
                'bet'  => $this->theme->viewPart('poker-player-chips'),
            ],
            'next'   => [
                'seat' => $nextPlayer
            ],
            'dom'    => $this->getDomChanges($player),
        ];
        //hand_hook();
    }

    public function dealCards($data, $player)
    {
        $logic = ($player->seat === $player->game->dealer) ? true : false;
        $logic = $this->addons->get_hooks(
            array(
                'state'   => $logic,
                'content' => $logic,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'hand4_logic'
        ));

        if (!$logic)
            return false;
        
        $cards = ['AD', '2D', '3D', '4D', '5D', '6D', '7D', '8D', '9D', '10D', 'JD', 'QD', 'KD', 'AC', '2C', '3C', '4C', '5C', '6C', '7C', '8C', '9C', '10C', 'JC', 'QC', 'KC', 'AH', '2H', '3H', '4H', '5H', '6H', '7H', '8H', '9H', '10H', 'JH', 'QH', 'KH', 'AS', '2S', '3S', '4S', '5S', '6S', '7S', '8S', '9S', '10S', 'JS', 'QS', 'KS'];
    
        if ($player->game->gamestyle == 'o'):
            $cardpos = ['card1', 'card2', 'card3', 'card4', 'card5', 'p1card1', 'p1card2', 'p1card3', 'p1card4', 'p2card1', 'p2card2', 'p2card3', 'p2card4', 'p3card1', 'p3card2', 'p3card3', 'p3card4', 'p4card1', 'p4card2', 'p4card3', 'p4card4', 'p5card1', 'p5card2', 'p5card3', 'p5card4', 'p6card1', 'p6card2', 'p6card3', 'p6card4', 'p7card1', 'p7card2', 'p7card3', 'p7card4', 'p8card1', 'p8card2', 'p8card3', 'p8card4', 'p9card1', 'p9card2', 'p9card3', 'p9card4', 'p10card1', 'p10card2', 'p10card3', 'p10card4'];
            $numcards = 45;
        else:
            $cardpos = ['card1', 'card2', 'card3', 'card4', 'card5', 'p1card1', 'p1card2', 'p2card1', 'p2card2', 'p3card1', 'p3card2', 'p4card1', 'p4card2', 'p5card1', 'p5card2', 'p6card1', 'p6card2', 'p7card1', 'p7card2', 'p8card1', 'p8card2', 'p9card1', 'p9card2', 'p10card1', 'p10card2'];
            $numcards = 25;
        endif;
    
        $i         = 0;
        $pick      = [];
        $gameCards = [];

        while ($i < $numcards):
            $select = $cards[ mt_rand(0, 51) ];

            if ( ! in_array($select, $pick) ):
                $pick[$i] = $select;
                $gameCards[ $cardpos[$i] ] = encrypt_card($select);
                $i++;
            endif;
        endwhile;

        $this->gamedata(array_merge(
            $gameCards,
            [
                'hand'     => 5,
                'lastmove' => time(),
            ]
        ), $player);

        $player->lastAct = time();
        return [
            'type' => 'deal-cards',
            'log'  => $this->log('', GAME_MSG_DEAL_CARDS, $player),
            'dom'  => $this->getDomChanges($player),
        ];
        # hand_hook();
    }

    public function getMyCards($data, $player)
    {
        if ( !$player->seat )
            return;
        
        $opsTheme   = $this->theme;
        $game_style = $player->game->gamestyle;
        require __DIR__ . '/includes/card_sys.php';

        $card1   = decrypt_card($player->card1);
        $card2   = decrypt_card($player->card2);
        $animate = boolval($player->game->tableanim) ? '-anim' : '';

        $this->gamedata(['lastmove' => time()], $player);

        $player->lastAct = time();

        return [
            'type'       => 'get-my-cards',
            'table'      => $this->getTableData($player),
            'player'     => $this->getPlayerData($player),
            'othercards' => $playerCard['facedown-anim'] . $playerCard['facedown-anim'],
            'dom'        => $this->getDomChanges($player),
        ];
        
        return [
            'type' => 'get-my-cards',
            'seat' => $player->seat,
            'move' => $player->game->move,
            'card1' => [
                'id'   => $card1,
                'html' => isset($playerCard[$card1 . $animate]) ? $playerCard[$card1 . $animate] : '',
            ],
            'card2' => [
                'id'   => $card2,
                'html' => isset($playerCard[$card2 . $animate]) ? $playerCard[$card2 . $animate] : '',
            ],
            'seats'      => array_map(function($x){
                return $x->seat;
            }, $this->getPlayers($player)),
            'othercards' => $playerCard['facedown-anim'] . $playerCard['facedown-anim'],
            'dom'        => $this->getDomChanges($player),
        ];
    }

    public function getMyTurn($data, $player)
    {
        if ($player->seat !== $player->game->move):
            return ['type' => 'not-my-turn'];
        endif;

        $buttons  = $this->getButtons($data, $player);

        $player->lastAct = time();
        return [
            'type'    => 'get-my-turn',
            'buttons' => $buttons['html'],
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function startTimer($data, $player)
    {
        $timeleft = ($player->game->lastmove + MOVETIMER) - time();
        $timeleft = $timeleft < 1 ? 0 : $timeleft;

        $player->lastAct = time();
        return [
            'type'       => 'start-timer',
            'table'      => $this->getTableData($player),
            'timeleft'   => $timeleft,
            'dom'        => $this->getDomChanges($player),
        ];
    }

    public function playerAction($data, $player)
    {
        $action = $data->action;

        switch ($action):
            case 'hide-my-cards':
                $this->playerdata(['show_cards' => 0], $player);
                return false;
                break;
            
            case 'show-my-cards':
                $this->playerdata(['show_cards' => 1], $player);
                return false;
                break;
            
            default:
                break;
        endswitch;

        $opsTheme   = $this->theme;
        $game_style = $player->game->gamestyle;
        require __DIR__ . '/includes/card_sys.php';

        $badPlayer = $player->seat === $player->game->move || $player->seat === $player->game->dealer && $action === 'behalf' || $this->isAddonActive('bots') && $player->seat === $player->game->botgod && $action === 'bot' ? false : true;

        if ($badPlayer):
            return ['type' => 'bad-player'];
        endif;

        $mvr = $player->game->move > 0 && $player->game->move < 11 ? $player->game->{"p{$player->game->move}name"} : '';
        
        if (empty($mvr)):
            return ['type' => 'bad-player'];
        endif;

        $mover = $this->players[ $mvr ];

        if ($action === 'bot'):
            $randActions   = [];
            $callAction    = $player->game->bet > $mover->bet ? 'call' : 'check';
            $randActions[] = $callAction;

            if ($callAction === 'call' && $player->game->hand > 5):
                $randActions[] = 'call';
                $randActions[] = 'call';
                $randActions[] = 'fold';
            endif;

            $action = $randActions[mt_rand(0, count($randActions) - 1)];
            $mover->lastAct = time();
        endif;

        $betActions = ['behalf', 'auto', 'check', 'call', 'allin', 'fold', 'raise'];
        $time       = time();
        $max        = intval($mover->pot);
        $isBet      = in_array($action, $betActions) ? true : false;
        
        if ($action === 'raise'):
            $raise  = intval($data->amount);
            $action = $raise >= intval($mover->pot) ? 'allin' : 'raise';

            if ($raise < 1 || $raise > $max):
                $isBet = false;
            endif;
        endif;

        $okay = ($this->getPlayers($mover, true) > 1 && ($this->isStraddling($mover) || ($this->isInterplay($mover) && $isBet == true))) ? true : false;
        $okay = $this->addons->get_hooks(
            [
                'state'   => $okay,
                'content' => $okay,
                'wss'     => $this,
                'player'  => $player,
            ],
            [
                'page'     => 'includes/player_move.php',
                'location' => 'everything_okay_logic'
            ]
        );

        if ( !$okay ):
            return ['type' => 'player-not-okay'];
        endif;

        $process     = false;
        $processed   = false;
        $goAllIn     = false;
        $nextPlayer  = $this->nextPlayer($mover);
        $nextHand    = intval($mover->game->hand);
        $tablebet    = $mover->game->bet;
        $tablepot    = $mover->game->pot;
        $lastbet     = $mover->game->lastbet;
        $dealCards   = false;
        $log         = false;

        switch ($mover->game->hand):
            case 5:
                $foldStatClm = 'fold_pf';
                break;
            
            case 6:
            case 7:
                $foldStatClm = 'fold_f';
                break;
            
            case 8:
            case 9:
                $foldStatClm = 'fold_t';
                break;
            
            case 10:
            case 11:
                $foldStatClm = 'fold_r';
                break;
            
            default:
                $foldStatClm = false;
                break;
        endswitch;

        switch ($mover->game->hand):
            case 5:
            case 7:
            case 9:
            case 11:
                if ( $action !== 'raise' && $nextPlayer == $this->lastBet($mover) ):
                    $nextHand++;
                    $dealCards = true;
                endif;
                break;
            
            case 6:
            case 8:
            case 10:
            case 26:
                $nextHand++;
                break;
            
            default:
                return ['type' => 'wrong-hand'];
                break;
        endswitch;
        
        switch ($action):
            case 'auto':
            case 'behalf':
                if ($tablebet > intval($mover->bet)):
                    $this->gamedata(array_merge(
                        [
                            "p{$mover->seat}bet" => "F{$mover->bet}",
                            'move'                => $nextPlayer,
                            'hand'                => $nextHand,
                            'lastmove'            => time(),
                        ],
                        $mover->seat === $mover->game->dealer && $nextPlayer > 0 ? [
                            'dealer' => $nextPlayer
                        ] : [],
                    ), $mover);

                    $log = $this->log($mover, GAME_PLAYER_FOLDS);

                    if ($foldStatClm):
                        $this->statdata([$foldStatClm => "::>{$foldStatClm} + 1"], $mover);
                    endif;
                else:
                    $this->gamedata([
                        'move'                => $nextPlayer,
                        'hand'                => $nextHand,
                        'lastmove'            => time(),
                    ], $mover);
                    $this->statdata(['checked' => '::>checked + 1'], $mover);
                    $log = $this->log($mover, GAME_PLAYER_CHECKS);
                endif;

                $processed = true;
                break;
            case 'fold':
                $mover->nofold_handsplayed = 0;
                $mover->nofold_handswon    = 0;
                
                $this->gamedata(array_merge(
                    [
                        "p{$mover->seat}bet" => "F{$mover->bet}",
                        'move'                => $nextPlayer,
                        'lastmove'            => time(),
                    ],
                    empty($nextHand) ? [] : ['hand' => $nextHand],
                    $mover->seat === $mover->game->dealer && $nextPlayer > 0 ? [
                        'dealer' => $nextPlayer
                    ] : [],
                ), $mover);
                $this->playerdata(['lastmove' => time()], $mover);
                $log = $this->log($mover, GAME_PLAYER_FOLDS);

                if ($mover->game->hand < 6)
                    $this->statdata(['fold_pf' => '::>fold_pf + 1'], $mover);
                elseif ($mover->game->hand < 8)
                    $this->statdata(['fold_f' => '::>fold_f + 1'], $mover);
                elseif ($mover->game->hand < 10)
                    $this->statdata(['fold_t' => '::>fold_t + 1'], $mover);
                else
                    $this->statdata(['fold_r' => '::>fold_r + 1'], $mover);
                
                $processed = true;
                break;
            case 'check':
                $this->statdata(['checked' => '::>checked + 1'], $mover);
                $this->playerdata(['lastmove' => time()], $mover);
                $this->gamedata([
                    'move'     => $nextPlayer,
                    'lastmove' => time(),
                    'hand'     => $nextHand
                ], $mover);

                $log = $this->log($mover, GAME_PLAYER_CHECKS);
                $processed = true;
                break;
            
            case 'call':
                $process = true;
                $callBet = $tablebet - intval($mover->bet);
                $goAllIn = intval($mover->pot) <= $callBet ? true : false;

                if ( !$goAllIn ):
                    $potleft   = intval($mover->pot) - $callBet;
                    $tablepot  = $tablepot + $callBet;
                    $pbet      = $tablebet;
                    $tablebet2 = $tablebet;
                endif;

                $this->statdata(['called' => '::>called + 1'], $mover);
                $log = $this->log($mover, GAME_PLAYER_CALLS . ' ' . money_small($callBet, true, $this->moneyPrefix($mover)));
                break;

            case 'allin':
                $goAllIn = true;
                $this->statdata(['allin' => '::>allin + 1'], $mover);
                break;
            
            case 'raise':
                $diff     = $tablebet - intval($mover->bet);
                $checkBet = $diff + $raise;

                if ($checkBet >= intval($mover->pot))
                    $goAllIn = true;
                else
                {
                    $process   = true;
                    $pbet      = $tablebet + $raise;
                    $tablepot += $checkBet;
                    $potleft   = intval($mover->pot) - $checkBet;
                    $tablebet2 = $tablebet + $raise;
                }

                $this->statdata(['bet' => '::>bet + 1'], $mover);
                $log = $this->log($mover, GAME_PLAYER_RAISES . ' ' . money_small($raise, true, $this->moneyPrefix($mover)));
                break;
            
            default:
                return ['type' => 'bad-action'];
                break;
        endswitch;

        if ($goAllIn)
        {
            $process   = true;
            $diff      = $tablebet - intval($mover->bet);
            $raise     = intval($mover->pot) - $diff;
            $tablepot += intval($mover->pot);
            $tablebet2 = $raise > 0 ? $tablebet + $raise : $tablebet;
            $pbet      = intval($mover->bet) + intval($mover->pot);
            $potleft   = 0;

            $log = $this->log($mover, GAME_PLAYER_GOES_ALLIN);

            $mover->went_allin = true;
            $mover->allin_this_hand = true;

            $mover->cont_allin_count++;
            $mover->cont_allin_win_count++;
        }

        if ( $process ):
            $lastbet = ($tablebet2 > $tablebet || $lastbet == 0) ? $mover->seat . '|' . $tablebet : $lastbet;

            $this->gamedata(array_merge(
                [
                    'pot'                 => $tablepot,
                    'bet'                 => $tablebet2,
                    'lastbet'             => $lastbet,
                    'move'                => $nextPlayer,
                    'lastmove'            => time(),
                    "p{$mover->seat}bet" => $pbet,
                    "p{$mover->seat}pot" => $potleft,
                ],
                empty($nextHand) ? [] : ['hand' => $nextHand]
            ), $mover);
            $this->playerdata(['lastmove' => time()], $mover);

            $processed = true;
        endif;

        if ( !$processed ):
            return ['type' => 'not-processed'];
        endif;

        $showdown   = in_array($this->gameStatus($mover), ['showdown', 'allfold']) || $nextHand === 12 ? true : false;

        $tableData = $this->getTableData($mover);
        if ($dealCards):
            switch ($nextHand):
                case 6:
                    $tableData['cardlog'] = $this->log('', GAME_MSG_DEAL_FLOP, $mover);
                    $tableData['cards']   = [
                        1 => $tableData['cards'][1],
                        2 => $tableData['cards'][2],
                        3 => $tableData['cards'][3],
                    ];
                    break;
                
                case 8:
                    $tableData['cardlog'] = $this->log('', GAME_MSG_DEAL_TURN, $mover);
                    $tableData['cards']   = [
                        4 => $tableData['cards'][4],
                    ];
                    break;
                
                case 10:
                    $tableData['cardlog'] = $this->log('', GAME_MSG_DEAL_RIVER, $mover);
                    $tableData['cards']   = [
                        5 => $tableData['cards'][5],
                    ];
                    break;
                
                default:
                    break;
            endswitch;
        else:
            $tableData['cards'] = [];
        endif;

        $this->theme->addVariable('player', array(
			'name'   	  => $mover->id,
			'pot'    	  => money($mover->pot, true, $this->moneyPrefix($mover)),
			'avatar' 	  => get_ava($mover->id),
			'before_name' => $this->addons->get_hooks(
				array(
                    'wss'    => $this,
                    'player' => $player,
				),
				array(
					'page'     => 'wss',
					'location' => 'player_before_name'
				)
			)
		));
        $this->theme->addVariable('bet_money', money_small($mover->bet, true, $this->moneyPrefix($mover)));

        $this->addons->get_hooks(
            array(),
            array(
                'page'     => 'wss',
                'location' => 'after_move'
            )
        );

        $this->addons->get_hooks(
            [
                'wss'    => $this,
                'player' => $player,
            ],
            [
                'page'     => 'wss',
                'location' => 'hand_change'
            ]
        );

        $player->lastAct = time();
        return [
            'type'   => 'player-action',
            'log'    => $log,
            'action' => $action,
            'table' => $tableData,
            'player' => [
                'seat' => $mover->seat,
                'info' => $this->theme->viewPart('poker-player-info'),
                'bet'  => in_array($this->getBet($mover), ['FOLD', 0]) ? '' : $this->theme->viewPart('poker-player-chips'),
            ],
            'next' => [
                'seat' => $nextPlayer
            ],
            'showdown' => $showdown,
            'dom'      => $this->getDomChanges($player),
        ];
    }

    public function showdown($data, $player)
    {
        if ($player->seat !== $player->game->dealer)
            return;
        
        $opsTheme   = $this->theme;
        $game_style = $player->game->gamestyle;
        require __DIR__ . '/includes/card_sys.php';

        $gameStatus = $this->gameStatus($player);
        $numOfCards = $game_style == 'o' ? 4 : 2;
        $animClass  = boolval($player->game->tableanim) ? '-anim' : '';

        $player->lastAct = time();
        return [
            'type'    => 'showdown',
            'log'     => $this->log('', GAME_MSG_SHOWDOWN, $player),
            'table'   => $this->getTableData($player),
            'player'  => [
                'seat' => $player->seat,
            ],
            'players' => $this->getPlayersData($player, true),
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function finish($data, $player)
    {
        if ($player->seat !== $player->game->dealer)
            return;
        
        $tableCards = $this->tableCards($player);
        $multiwin   = $this->findWinners($player);
        $winners    = $multiwin[1] == '' ? 1 : 2;
        $winSeat    = $multiwin[0];
        $winPlyrId  = $player->game->{"p{$winSeat}name"};
        $winlog     = $this->evaluateWin($player, $multiwin[0]);
        $winlog     = empty($winlog) ? __('the highest card', 'core') : 'a ' . $winlog;
        $i          = 0;

        if (empty($winPlyrId) || !isset($this->players[ $winPlyrId ])):
            return ['type' => 'no-winner'];
        endif;

        $winPlayer = $this->players[ $winPlyrId ];

        if ($winners > 1):
            $log = $this->log('', GAME_MSG_SPLIT_POT_RESULT . ' ' . $winlog, $player, true);
        else:
            $nonFoldedPlayers = $this->getNumberOfPlayers($player) - $this->getFoldedPlayers($player);

            if ($nonFoldedPlayers == 1):
                $log = $this->log($winPlayer, GAME_MSG_ALLFOLD, false, true);
            else:
                $log = $this->log($winPlayer, ' wins the hand with ' . $winlog, false, true);
            endif;
        endif;

        while ($multiwin[$i] != ''):
            $seat     = $multiwin[$i];
            $playerId = $player->game->{"p{$seat}name"};

            if (empty($playerId)):
                $i++;
                continue;
            endif;

            $seatPlayer = $this->players[ $playerId ];

            $seatPlayer->nofold_handswon++;
            $seatPlayer->noleave_handswon++;

            if ($seatPlayer->went_allin && $seatPlayer->allin_this_hand):
                $seatPlayer->cont_allin_win_count++;
            endif;

            if ($seatPlayer->nofold_handswon > $this->statdata(['nofold_handswon'], $player)):
                $this->statdata(['nofold_handswon' => $seatPlayer->nofold_handswon], $player);
            endif;
            
            if ($seatPlayer->noleave_handswon > $this->statdata(['noleave_handswon'], $player)):
                $this->statdata(['noleave_handswon' => $seatPlayer->noleave_handswon], $player);
            endif;

            $this->statdata(['handswon' => '::>handswon + 1'], $player);
            $i++;
        endwhile;

        $this->addons->get_hooks(
            [
                'wss'     => $this,
                'player'  => $player,
            ],
            [
                'page'     => 'wss',
                'location' => 'before_distpot'
            ]
        );

        $this->distpot($player);
        list($smallBlinds, $bigBlinds) = $this->getBlinds($player);

        for ($i = 1; $i < 11; $i++):
            $plyr = $player->game->{'p' . $i . 'name'};

            if ( empty($plyr) || !isset($this->players[ $plyr ]) || $this->players[ $plyr ]->seat === false )
                continue;
            
            $seatPlayer = $this->players[ $plyr ];

            if (intval($seatPlayer->pot) < $bigBlinds):
                $this->theme->addVariable('seat_number', $seatPlayer->seat);
                $busts[] = [
                    'seat'   => $seatPlayer->seat,
                    'player' => $seatPlayer->id,
                    'avatar' => $this->theme->viewPart('poker-player-circle'),
                ];
                $this->kickPlayer($seatPlayer);
            endif;
        endfor;
        /** */

        $this->gamedata([
            'hand'     => 0,
            'pot'      => 0,
            'lastmove' => time(),
        ], $player);

        $player->lastAct = time();

        return [
            'type'  => 'finish',
            'log'   => $log,
            'table' => $this->getTableData($player),
            'dom'   => $this->getDomChanges($player),
        ];
    }

    public function distpot($player)
    {
        $pots = [''];

        for ($i = 1; $i < 11; $i++):
            $pots[] = $this->getPot($player, $i);
        endfor;
    
        $wins    = [];
        $winners = $this->findWinners($player);
    
        $totalWinnerBets = 0;
        $z = 0;

        while (isset($winners[$z]) && $winners[$z] != ''):
            $totalWinnerBets += $this->getBetMath($player, $winners[$z]);
            $z++;
        endwhile;
    
        $z = 0;
        while (isset($winners[$z]) && $winners[$z] != ''):
            $winner         = $winners[$z];
            $winnerName     = $player->game->{"p{$winner}name"};
            $winnerBet      = $this->getBet($player, $winner);
            $cut            = $totalWinnerBets > 0 ? $winnerBet / $totalWinnerBets : 0;
            $pots[$winner] += $winnerBet;
    
            for ($i = 1; $i < 11; $i++):
                $loserPot = $this->getPot($player, $i);
                $loserBet = $this->getBetMath($player, $i) * $cut;
                
                if ($winner != $i && $loserBet > 0 && !in_array($i, $winners))
                {
                    if ($winnerBet >= $loserBet):
                        $pots[$winner] += $loserBet;
                    else:
                        $pots[$winner] += $winnerBet;
                        $pots[$i]      += ($loserBet - $winnerBet);
                    endif;
    
                    $wins[] = $this->players[ $winnerName ];
                }
            endfor;
    
            $z++;
        endwhile;
    
        $pots = $this->addons->get_hooks(
            array(
                'content' => $pots,
                'winners' => $wins,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'wss',
                'location' => 'distpots'
            )
        );

        $playerPots = [];
        for ($i = 1; $i < 11; $i++):
            $playerPots["p{$i}pot"] = roundpot($pots[ $i ]);
        endfor;

        $this->gamedata($playerPots, $player);
    }

    public function findWinners($player)
    {
        $x   = 0;
        $pts = -1;
    
        $bestCards  = [];
        $finalCards = [];
        $multiwin   = [];

        $gameStatus = $this->gameStatus($player);

        for ($i = 1; $i < 11; $i++):
            if ( !$this->isPlayerInGame($player, $i) ):
                continue;
            endif;

            $iBet = $this->getBet($player, $i);

            if ($gameStatus === 'allfold' && $iBet !== 'FOLD' && $iBet > 0):
                $multiwin = [$i];
                break;
            endif;

            if ($player->game->gamestyle == 'o'):
                $winpts = $this->addons->get_hooks(
                    array(
                        'wss'     => $this,
                        'player'  => $player,
                        'seat'    => $i,
                        'content' => 0
                    ),
                    array(
                        'page'     => 'includes/poker_inc.php',
                        'location' => 'omaha_logic'
                    )
                );
            else:
                $winpts = $this->evaluateTexas($player, $i);
            endif;

            if ($winpts > $pts):
                $x           = 0;
                $multiwin[0] = $i;
                $multiwin[1] = '';
                $multiwin[2] = '';
                $multiwin[3] = '';
                $multiwin[4] = '';
                $pts         = $winpts;
                $finalCards  = $bestCards;
            elseif ($winpts == $pts && $winpts > 0): // $winpts == $pts && $winpts > 0
                $x++;
                $multiwin[$x] = $i;
            endif;
        endfor;
    
        return [
            isset($multiwin[0]) ? $multiwin[0] : '',
            isset($multiwin[1]) ? $multiwin[1] : '',
            isset($multiwin[2]) ? $multiwin[2] : '',
            isset($multiwin[3]) ? $multiwin[3] : '',
            isset($multiwin[4]) ? $multiwin[4] : '',
            isset($multiwin[5]) ? $multiwin[5] : ''
        ];
    }

    public function evaluateTexas($player, $seat = false)
    {
        $points = 0;
        $seat   = $seat ? $seat : $player->seat;
        $plyr   = $player->game->{"p{$seat}name"};

        if (empty($plyr) || !isset($this->players[$plyr])):
            return $points;
        endif;

        $seatPlayer = &$this->players[$plyr];
        $hand       = array_merge(
            $this->tableCards($player),
            array_map(function($card){
                return decrypt_card($card);
            }, $seatPlayer->cards)
        );
        $flush      = [];
        $values     = [];
        $sortvalues = [];
        $hcs        = [];
        $orig       = ['J', 'Q', 'K', 'A'];
        $change     = [11, 12, 13, 14];
        $i          = 0;

        while (isset($hand[$i]) && $hand[$i] != '') {
            if (strlen($hand[$i]) == 2) {
                $flush[$i]      = substr($hand[$i], 1, 1);
                $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 1));
                $sortvalues[$i] = $values[$i];
            } else {
                $flush[$i]      = substr($hand[$i], 2, 1);
                $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 2));
                $sortvalues[$i] = $values[$i];
            }
            $i++;
        }

        sort($sortvalues);
        $pairmatch = '';
        $ispair    = array_count_values($values);
        $results   = array_count_values($ispair);
        $i         = 0;
        if (isset($results['2']) && $results['2'] == 1)
            $res = '1pair';
        if (isset($results['2']) && $results['2'] > 1)
            $res = '2pair';
        if (isset($results['3']) && $results['3'] > 0)
            $res = '3s';
        if (isset($results['4']) && $results['4'] > 0)
            $res = '4s';
        if ((isset($results['2'], $results['3']) && ($results['3'] > 0) && ($results['2'] > 0)) || (isset($results['3']) && $results['3'] > 1))
            $res = 'FH';
        $i         = 2;
        $z         = 0;
        $y         = 0;
        $multipair = [];
        while ($i < 15) {
            if (isset($ispair[$i]) && $ispair[$i] == 2) {
                $multipair[$z] = $i;
                $highpair      = $i;
                $z++;
            }
            if (isset($ispair[$i]) && $ispair[$i] == 3) {
                $threepair[$y] = $i;
                $high3pair     = $i;
                $y++;
            }
            $i++;
        }
        $bw = 6;
        $n  = 0;
        while ((isset($sortvalues[$bw]) && $sortvalues[$bw] != '') && ($n < 5)) {
            if (isset($res) && $res === '3s')
            {
                if (! in_array($sortvalues[$bw], $threepair))
                {
                    $hcs[$n] = $sortvalues[$bw];
                    $n++;
                }
            }
            else
            {
                if (! in_array($sortvalues[$bw], $multipair))
                {
                    $hcs[$n] = $sortvalues[$bw];
                    $n++;
                }
            }
            $bw--;
        }
        $h1    = isset($hcs[0]) ? $hcs[0] : 0;
        $h2    = (isset($hcs[1]) ? $hcs[1] : 0) / 10;
        $h3    = (isset($hcs[2]) ? $hcs[2] : 0) / 100;
        $h4    = (isset($hcs[3]) ? $hcs[3] : 0) / 1000;
        $h5    = (isset($hcs[4]) ? $hcs[4] : 0) / 10000;
        $high1 = $h1;
        $high2 = $h1 + $h2;
        $high3 = $h1 + $h2 + $h3;
        $high5 = $h1 + $h2 + $h3 + $h4 + $h5;
        if (isset($res) && ($res == '1pair' || $res == '2pair' || $res == 'FH')) {
            if ($res == '1pair') {
                $points = (($highpair * 10) + ($high3));
            }
            if ($res == '2pair') {
                sort($multipair);
                $pairs = count($multipair);
                if ($pairs == 3) {
                    $pr1 = $multipair[2];
                    $pr2 = $multipair[1];
                } else {
                    $pr1 = $multipair[1];
                    $pr2 = $multipair[0];
                }
                $points = ((($pr1 * 100) + ($pr2 * 10)) + $high1);
            }
            if ($res == 'FH') {
                sort($multipair);
                sort($threepair);
                $pairs  = count($multipair);
                $threes = count($threepair);
                if ($pairs == 1) {
                    $pr1 = $multipair[0];
                } else {
                    $pr1 = $multipair[1];
                }
                if ($threes == 1) {
                    $kry1 = $threepair[0];
                } else {
                    $kry1 = $threepair[1];
                    $kry2 = $threepair[0];
                }
                if (isset($kry2, $pr1) && $kry2 > $pr1)
                    $pr1 = $kry2;
                $points = (($kry1 * 1000000) + ($pr1 * 100000));
            }
        }
        if (isset($res) && $res == '3s') {
            $i = 2;
            while ($i < 15) {
                if (isset($ispair[$i]) && $ispair[$i] == 3) {
                    $points = ($i * 1000) + $high2;
                }
                $i++;
            }
        }
        if (isset($res) && $res == '4s') {
            $i = 2;
            while ($i < 15) {
                if (isset($ispair[$i]) && $ispair[$i] == 4) {
                    $points = $i * 10000000 + $high1;
                }
                $i++;
            }
        }
        $flushsuit = '';
        $isflush   = array_count_values($flush);
        if (isset($isflush['D']) && $isflush['D'] > 4)
            $flushsuit = 'D';
        if (isset($isflush['C']) && $isflush['C'] > 4)
            $flushsuit = 'C';
        if (isset($isflush['H']) && $isflush['H'] > 4)
            $flushsuit = 'H';
        if (isset($isflush['S']) && $isflush['S'] > 4)
            $flushsuit = 'S';
        if (isset($flushsuit) && $flushsuit != '') {
            $res        = $flushsuit . ' FLUSH DETECTED';
            $i          = 0;
            $x          = 0;
            $flusharray = [];
            while ($i < 7) {
                if ($flush[$i] == $flushsuit) {
                    $flusharray[$x] = $values[$i];
                    $x++;
                }
                $i++;
            }
            sort($flusharray);
            $basic    = 250000;
            $z        = count($flusharray) - 1;
            $c1       = $flusharray[$z] * 1000;
            $s1       = $flusharray[$z];
            $c2       = $flusharray[$z - 1] * 100;
            $s2       = $flusharray[$z - 1];
            $c3       = $flusharray[$z - 2] * 10;
            $s3       = $flusharray[$z - 2];
            $c4       = $flusharray[$z - 3];
            $s4       = $flusharray[$z - 3];
            $c5       = $flusharray[$z - 4] / 10;
            $s5       = $flusharray[$z - 4];
            $points   = $basic + $c1 + $c2 + $c3 + $c4 + $c5;
            $flushstr = false;
            $i        = 0;
            $x        = 0;
            while (isset($flusharray[$i]) && $flusharray[$i] != '') {
                if (isset($flusharray[$i], $flusharray[($i + 1)]) && $flusharray[$i] == ($flusharray[$i + 1] - 1)) {
                    $x++;
                    $h = $flusharray[$i] + 1;
                }
                $i++;
            }
            if ($x > 3)
                $points = $h * 100000000;
            if (($x > 3) && ($h == 14))
                $points = $h * 1000000000;
        }
        if (isset($flushsuit) && $flushsuit == '') {
            $straight = false;
            $i        = 0;
            $count    = 0;
            if ((isset($sortvalues[6]) && $sortvalues[6] == 14) && (isset($sortvalues[0]) && $sortvalues[0] == 2))
                $count = 1;
            while (isset($sortvalues[$i]) && $sortvalues[$i] != '') {
                if (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) == ($sortvalues[$i + 1] - 1)) {
                    $count++;
                    if ($count > 3) {
                        $straight = true;
                        $res      = 'STRAIGHT';
                        $h        = $sortvalues[$i] + 1;
                        $points   = $h * 10000;
                    }
                } elseif (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) != ($sortvalues[$i + 1])) {
                    $count = 0;
                }
                $i++;
            }
        }
        if (isset($res) && $res == '') {
            $points = $high5;
        }
        return $points;
    }

    public function evaluateWin($player, $seat = false)
    {
        $seat       = $seat ? $seat : $player->seat;
        $plyr       = $player->game->{"p{$seat}name"};
        $points     = 0;
        $finalCards = [];
    
        if ( empty($seat) || empty($plyr) ):
            return '';
        endif;

        $seatPlayer = isset($this->players[ $plyr ]) ? $this->players[ $plyr ] : false;

        if ( !$seatPlayer ):
            return '';
        endif;

        $hand = array_merge(
            $this->tableCards($player),
            array_map(function($card){
                return decrypt_card($card);
            }, $seatPlayer->cards)
        );
    
        $flush      = [];
        $values     = [];
        $sortvalues = [];
        $orig       = ['J', 'Q', 'K', 'A'];
        $change     = [11, 12, 13, 14];
        $i          = 0;

        while (isset($hand[$i]) && $hand[$i] != ''):
            if (strlen($hand[$i]) == 2):
                $flush[$i]      = substr($hand[$i], 1, 1);
                $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 1));
                $sortvalues[$i] = $values[$i];
            else:
                $flush[$i]      = substr($hand[$i], 2, 1);
                $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 2));
                $sortvalues[$i] = $values[$i];
            endif;

            $i++;
        endwhile;

        sort($sortvalues);
        $pairmatch     = '';
        $ispair        = array_count_values($values);
        $results       = array_count_values($ispair);
        $i             = 0;
        $outputvalues  = ['', '', '2s', '3s', '4s', '5s', '6s', '7s', '8s', '9s', '10s', 'Jacks', 'Queens', 'Kings', 'Aces'];
        $outputvalues2 = ['', '', 2, 3, 4, 5, 6, 7, 8, 9, 10, 'Jack', 'Queen', 'King', 'Ace'];

        if (isset($results['2']) && $results['2'] == 1)
            $res = '1pair';
        if (isset($results['2']) && $results['2'] > 1)
            $res = '2pair';
        if (isset($results['3']) && $results['3'] > 0)
            $res = '3s';
        if (isset($results['4']) && $results['4'] > 0)
            $res = '4s';
        if ((isset($results['2'], $results['3']) && ($results['3'] > 0) && ($results['2'] > 0)) || (isset($results['3']) && $results['3'] > 1))
            $res = 'FH';
        if (isset($res) && ($res == '1pair' || $res == '2pair' || $res == 'FH')) {
            $i         = 2;
            $z         = 0;
            $y         = 0;
            $multipair = [];
            while ($i < 15) {
                if (isset($ispair[$i]) && $ispair[$i] == 2) {
                    $multipair[$z] = $i;
                    $z++;
                }
                if (isset($ispair[$i]) && $ispair[$i] == 3) {
                    $threepair[$y] = $i;
                    $y++;
                }
                $i++;
            }
            $HCS = [];
            if (isset($res) && $res == '1pair') {
                if ($multipair[0] == 2)
                    $Xres = $outputvalues[2];
                if ($multipair[0] == 3)
                    $Xres = $outputvalues[3];
                if ($multipair[0] == 4)
                    $Xres = $outputvalues[4];
                if ($multipair[0] == 5)
                    $Xres = $outputvalues[5];
                if ($multipair[0] == 6)
                    $Xres = $outputvalues[6];
                if ($multipair[0] == 7)
                    $Xres = $outputvalues[7];
                if ($multipair[0] == 8)
                    $Xres = $outputvalues[8];
                if ($multipair[0] == 9)
                    $Xres = $outputvalues[9];
                if ($multipair[0] == 10)
                    $Xres = $outputvalues[10];
                if ($multipair[0] == 11)
                    $Xres = $outputvalues[11];
                if ($multipair[0] == 12)
                    $Xres = $outputvalues[12];
                if ($multipair[0] == 13)
                    $Xres = $outputvalues[13];
                if ($multipair[0] == 14)
                    $Xres = $outputvalues[14];
                $res = ' ' . WIN_PAIR . ' ' . $Xres;
                $this->statdata(['play_1pair' => '::>play_1pair + 1'], $player);
            }
            if (isset($res) && $res == '2pair') {
                sort($multipair);
                $pairs = count($multipair);
                if ($pairs == 3) {
                    $pr1 = $multipair[2];
                    $pr2 = $multipair[1];
                } else {
                    $pr1 = $multipair[1];
                    $pr2 = $multipair[0];
                }
                if ($pr1 == 3)
                    $Xres = $outputvalues[3];
                if ($pr1 == 4)
                    $Xres = $outputvalues[4];
                if ($pr1 == 5)
                    $Xres = $outputvalues[5];
                if ($pr1 == 6)
                    $Xres = $outputvalues[6];
                if ($pr1 == 7)
                    $Xres = $outputvalues[7];
                if ($pr1 == 8)
                    $Xres = $outputvalues[8];
                if ($pr1 == 9)
                    $Xres = $outputvalues[9];
                if ($pr1 == 10)
                    $Xres = $outputvalues[10];
                if ($pr1 == 11)
                    $Xres = $outputvalues[11];
                if ($pr1 == 12)
                    $Xres = $outputvalues[12];
                if ($pr1 == 13)
                    $Xres = $outputvalues[13];
                if ($pr1 == 14)
                    $Xres = $outputvalues[14];
                if ($pr2 == 2)
                    $Xres2 = $outputvalues[2];
                if ($pr2 == 3)
                    $Xres2 = $outputvalues[3];
                if ($pr2 == 4)
                    $Xres2 = $outputvalues[4];
                if ($pr2 == 5)
                    $Xres2 = $outputvalues[5];
                if ($pr2 == 6)
                    $Xres2 = $outputvalues[6];
                if ($pr2 == 7)
                    $Xres2 = $outputvalues[7];
                if ($pr2 == 8)
                    $Xres2 = $outputvalues[8];
                if ($pr2 == 9)
                    $Xres2 = $outputvalues[9];
                if ($pr2 == 10)
                    $Xres2 = $outputvalues[10];
                if ($pr2 == 11)
                    $Xres2 = $outputvalues[11];
                if ($pr2 == 12)
                    $Xres2 = $outputvalues[12];
                if ($pr2 == 13)
                    $Xres2 = $outputvalues[13];
                $res = WIN_2PAIR . ' ' . $Xres . ' and ' . $Xres2;
                $this->statdata(['play_2pair' => '::>play_2pair + 1'], $player);
            }
            if (isset($res) && $res == 'FH') {
                $res = WIN_FULLHOUSE;
                $this->statdata(['play_fullhouse' => '::>play_fullhouse + 1'], $player);
            }
        }
        if (isset($res) && $res == '3s') {
            $i = 2;
            while ($i < 15) {
                if (isset($ispair[$i]) && $ispair[$i] == 3) {
                    $res = WIN_SETOF3 . ' ' . $outputvalues[$i];
                }
                $i++;
            }
            $this->statdata(['play_3ofakind' => '::>play_3ofakind + 1'], $player);
        }
        if (isset($res) && $res == '4s') {
            while ($i < 15) {
                if (isset($ispair[$i]) && $ispair[$i] == 4) {
                    $res = WIN_SETOF4 . ' ' . $outputvalues[$i];
                }
                $i++;
            }
            $this->statdata(['play_4ofakind' => '::>play_4ofakind + 1'], $player);
        }
        $flushsuit = '';
        $isflush   = array_count_values($flush);
        if (isset($isflush['D']) && $isflush['D'] > 4)
            $flushsuit = 'D';
        if (isset($isflush['C']) && $isflush['C'] > 4)
            $flushsuit = 'C';
        if (isset($isflush['H']) && $isflush['H'] > 4)
            $flushsuit = 'H';
        if (isset($isflush['S']) && $isflush['S'] > 4)
            $flushsuit = 'S';
        if (isset($flushsuit) && $flushsuit != '') {
            $i          = 0;
            $x          = 0;
            $flusharray = array();
            while ($i < 7) {
                if (isset($flush[$i]) && $flush[$i] == $flushsuit) {
                    $flusharray[$x] = $values[$i];
                    $x++;
                }
                $i++;
            }
            sort($flusharray);
            $z        = count($flusharray) - 1;
            $res      = ' ' . $outputvalues2[$flusharray[$z]] . ' ' . WIN_FLUSH;
            $this->statdata(['play_flush' => '::>play_flush + 1'], $player);
            $flushstr = false;
            $i        = 0;
            $x        = 0;
            while (isset($flusharray[$i]) && $flusharray[$i] != '') {
                if (isset($flusharray[$i], $flusharray[($i + 1)]) && $flusharray[$i] == ($flusharray[$i + 1] - 1)) {
                    $x++;
                    $h = $flusharray[$i] + 1;
                }
                $i++;
            }
            if ($x > 3)
            {
                $res = $outputvalues2[$flusharray[$z]] . ' ' . WIN_STRAIGHT_FLUSH;
                $this->statdata(['play_straightflush' => '::>play_straightflush + 1'], $player);
            }
            if (($x > 3) && ($h == 14))
            {
                $res = WIN_ROYALFLUSH;
                $this->statdata(['play_royalflush' => '::>play_royalflush + 1'], $player);
            }
        }
        if (isset($flushsuit) && $flushsuit == '') {
            $lows     = false;
            $straight = false;
            $i        = 0;
            $count    = 0;
            if (isset($sortvalues[6], $sortvalues[0]) && ($sortvalues[6] == 14) && ($sortvalues[0] == 2)) {
                $count = 1;
                $lows  = true;
            }
            while (isset($sortvalues[$i]) && $sortvalues[$i] != '') {
                if (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) == ($sortvalues[$i + 1] - 1)) {
                    $count++;
                    if ($count > 3) {
                        $straight = true;
                        $h        = $sortvalues[$i] + 1;
                        $res      = ' ' . $outputvalues2[$h] . ' ' . WIN_STRAIGHT;
                        if (($lows == true) && ($h == 5) && ($count == 4))
                            $res = 'low straight';
    
                        $this->statdata(['play_straight' => '::>play_straight + 1'], $player);
                    }
                } elseif (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) != $sortvalues[$i + 1]) {
                    $count = 0;
                }
                $i++;
            }
        }
        if (isset($res) && $res == '') {
            $hc1 = $sortvalues[6];
            $res = $outputvalues2[$hc1] . ' ' . WIN_HIGHCARD;
        }
        return isset($res) ? $res : '';
    }

    public function tableCards($player)
    {
        return [
            decrypt_card($player->game->card1),
            decrypt_card($player->game->card2),
            decrypt_card($player->game->card3),
            decrypt_card($player->game->card4),
            decrypt_card($player->game->card5)
        ];
    }

    public function isPlayerInGame($player, $seat = false)
    {
        $seat = $seat ? $seat : $player->seat;
        return (
            !empty($player->game->{"p{$seat}name"})
            &&  $this->getBet($player, $seat) > 0
            && $this->getBet($player, $seat) != 'FOLD') ? true : false;
    }

    public function getButtons($data, $player)
    {
        $checkround = ($player->seat == $this->lastBet($player) && $this->checkBets($player) == true && ($player->game->hand == 5 || $player->game->hand == 7 || $player->game->hand == 9 || $player->game->hand == 11)) ? true : false;

        $playerbet = $this->getBetMath($player);

        if (
            ($this->isInterplay($player) || $this->isStraddling($player))
            && $player->seat === $player->game->move
            && $this->getPlayers($player, true) > 1
            && ! $this->isAllIn($player)
            && $this->getPlayersLeft($player, true) > 1
            && $checkround == false
        )
        {
            $button_limit = ['5', '100', '150', '250', '500', '15000', '25000',
            '50000', '100000', '250000', '500000', '1000000', '2500000', '5000000'];
            $button_array = ['50', '100', '150', '250', '500', '1000', '2500', '5000', '10000', '25000', '50000', '100000', '250000', '500000'];
            $button_display = [];

            $winpot = $this->getPot($player);
            $i = 13;
            $x = 4;
            while ($i >= 0 && $x >= 0):
                if (
                    $winpot > $button_limit[$i]
                    && ($winpot >= ($player->game->bet - $playerbet) + $button_array[$i])
                ):
                    $button_display[$x] = $button_array[$i];
                    $x--;
                endif;

                $i--;
            endwhile;

            $divider = 1;
            if (SMALLBETFUNC == 1)
                $divider = 1000;
            elseif (SMALLBETFUNC == 2)
                $divider = 100;
            elseif (SMALLBETFUNC == 3)
                $divider = 10;
            
            $this->theme->addVariable('divider', $divider);

            $multiplier = 1;
            if (SMALLBETFUNC == 1)
                $multiplier = 1000;
            elseif (SMALLBETFUNC == 2)
                $multiplier = 100;
            elseif (SMALLBETFUNC == 3)
                $multiplier = 10;
            $this->theme->addVariable('multiplier', $multiplier);

            list($smallBlinds, $bigBlinds) = $this->getBlinds($player);

            $initialRaise = $bigBlinds;
            if ($player->game->bet > $playerbet && $winpot >= ($player->game->bet - $playerbet))
                $initialRaise = ($player->game->bet - $playerbet) * 2;

            $initialRaise = $this->addons->get_hooks(
                array(
                    'content' => $initialRaise,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'initial_raise'
                )
            );
            $this->theme->addVariable('initial_raise', $initialRaise);
            $this->theme->addVariable('initial_raise_label', transfer_from($initialRaise));

            $raiseStep = $this->addons->get_hooks(
                array(
                    'content' => $initialRaise,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'raise_step'
                )
            );
            $this->theme->addVariable('raise_step', $raiseStep);
            $this->theme->addVariable('raise_step_label', transfer_from($raiseStep));

            $minRaise  = $this->addons->get_hooks(
                array(
                    'content' => $raiseStep,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'min_raise'
                )
            );
            $this->theme->addVariable('min_raise', $minRaise);
            $this->theme->addVariable('min_raise_label', transfer_from($minRaise));

            $maxRaise  = $this->addons->get_hooks(
                array(
                    'content' => $winpot,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'max_raise'
                )
            );
            $this->theme->addVariable('max_raise', $maxRaise);
            $this->theme->addVariable('max_raise_label', transfer_from($maxRaise));

            $displayFoldButton  = $this->addons->get_hooks(
                array(
                    'content' => (ALWAYSFOLD == 'yes' || $player->game->bet > $playerbet) ? true : false,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'display_fold_button'
                )
            );
            $displayCallButton  = $this->addons->get_hooks(
                array(
                    'content' => ($player->game->bet > $playerbet && $winpot >= ($player->game->bet - $playerbet)) ? true : false,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'display_call_button'
                )
            );
            $displayCheckButton = $this->addons->get_hooks(
                array(
                    'content' => ($displayCallButton == false && $winpot >= ($player->game->bet - $playerbet)) ? true : false,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'display_check_button'
                )
            );
            $displayAllInButton = $this->addons->get_hooks(
                array(
                    'content' => true,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'display_allin_button'
                )
            );
            $displayRaiseButton = $this->addons->get_hooks(
                array(
                    'content' => true,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'display_raise_button'
                )
            );

            $displayRebuyButton = false;

            if ( $player->game->tabletype == 'c' )
            {
                $maxbuyin = intval($player->game->tablelimit) == 0 ? intval($player->game->bbamount) * 100 : intval($player->game->tablelimit);
                $ppot     = intval($player->pot);

                if ( $ppot < $maxbuyin )
                {
                    $diff    = $maxbuyin - $ppot;
                    $current = $this->getStatPot($player);

                    if ( $diff > 0 && $current !== false && $current > 0 )
                        $displayRebuyButton = true;
                }
            }
            $displayRebuyButton = $this->addons->get_hooks(
                array(
                    'content' => $displayRebuyButton,
                    'wss'     => $this,
                    'player'  => $player,
                ),
                array(
                    'page'     => 'wss',
                    'location' => 'display_raise_button'
                )
            );

            $this->theme->addVariable('call_money', money_small( ($player->game->bet - $playerbet), true, $this->moneyPrefix($player)));

            if ($displayRaiseButton)
            {
                $raiseButtonLabels = array(
                    '2xBB'    => '2 big blinds',
                    '1xBB'    => '1 big blind',
                    '1xPOT'   => 'Pot',
                    '0.5xPOT' => '1/2 Pot'
                );

                $raiseButtons = array();
                foreach (json_decode(RAISEBUTTON, true) as $raiseButton)
                {
                    $raiseButtons[ $this->calcBeta($player, $raiseButton) ] = $raiseButtonLabels[$raiseButton];
                }

                $raiseLevels = $this->addons->get_hooks(
                    array(
                        'content' => $raiseButtons,
                        'wss'     => $this,
                        'player'  => $player,
                    ),
                    array(
                        'page'     => 'wss',
                        'location' => 'raise_levels'
                    )
                );

                $raiseLevelsHtml = '';
                foreach ($raiseLevels as $rlValue => $rlLabel)
                {
                    $this->theme->addVariable('level', array(
                        'value' => $rlValue,
                        'text'  => $rlLabel,
                    ));
                    $raiseLevelsHtml .= $this->theme->viewPart('poker-raise-level');
                }

                $this->theme->addVariable('raise_levels', $raiseLevelsHtml);

                $sliderHtml = $this->theme->viewPart('poker-raise-slider');
                $this->theme->addVariable('slider_html', $sliderHtml);
                $raiseJs = $this->theme->viewPart('poker-raise-js');
            }
            else
            {
                $raiseJs = $this->theme->viewPart('poker-raise-js-none');
            }

            $buttons = [
                'rebuy' => $displayRebuyButton ? $this->theme->viewPart('poker-button-rebuy') : '', #showRebuyButton
                'fold'  => $displayFoldButton ? $this->theme->viewPart('poker-button-fold') : '',
                'call'  => $displayCallButton ? $this->theme->viewPart('poker-button-call') : '',
                'check' => $displayCheckButton ? $this->theme->viewPart('poker-button-check') : '',
                'allin' => $displayAllInButton ? $this->theme->viewPart('poker-button-allin') : '',
                'raise' => $displayRaiseButton ? $this->theme->viewPart('poker-button-raise') . $raiseJs : '',
            ];

            $buttonsHtml = '';
            foreach ($buttons as $bi => $button):
                if ($bi === 'rebuy'):
                    continue;
                endif;
                
                $buttonsHtml .= $button;
            endforeach;
        }
        elseif ($this->isStraddle($player) && $player->game->hand == 1)
        {
            $buttons = [
                'straddle' => $this->theme->viewPart('poker-button-straddle'),
            ];

            $buttonsHtml = $buttons['straddle'];
        }
        else
        {
            $buttons = [];
            $buttonsHtml = '';
        }

        return [
            'buttons' => $buttons,
            'html'    => $buttonsHtml,
            'dom'     => $this->getDomChanges($player),
        ];
    }

    public function getPlayers($player, $count = false)
    {
        $i = 1;
        $x = $count ? 0 : [];
        
        while ($i < 11)
        {
            if (
                $player->game->{"p{$i}name"} != ''
                && (
                    $this->getPot($player, $i) > 0
                    || $this->getBet($player, $i) > 0
                )
                && $this->getPot($player, $i) != 'BUSTED'
            )
            {
                if ($count)
                    $x++;
                else
                    $x[] = $this->players[ $player->game->{"p{$i}name"} ];
            }

            $i++;
        }
    
        return $x;
    }

    public function getPlayersLeft($player, $count = false)
    {
        $i = 1;
        $x = $count ? 0 : [];

        while ($i < 11):
            if (
                $player->game->{"p{$i}name"} != ''
                && (
                    $this->getBet($player, $i) > 0
                    || $this->getPot($player, $i) > 0
                )
                && $this->getBet($player, $i) != 'FOLD'
            ):
                if ($count):
                    $x++;
                else:
                    $x[] = $this->players[ $player->game->{"p{$i}name"} ];
                endif;
            endif;

            $i++;
        endwhile;
    
        return $x;
    }

    public function isAllIn($player)
    {
        return is_numeric($player->bet) && intval($player->bet) > 0 && intval($player->pot) == 0 ? true : false;
    }

    public function lastBet($player)
    {
        $lb = explode('|', $player->game->lastbet);
        $i  = $lb[0];
    
        if (
            is_numeric($i)
            && $i > 0
            && empty($player->game->{"p{$i}name"})
        )
        {
            $nextPlayer = $this->nextPlayer($player, $i);
    
            if (! empty($nextPlayer))
            {
                $lastbet = "{$nextPlayer}|" . $player->game->{"p{$nextPlayer}bet"};
                $this->gamedata(['lastbet' => $lastbet], $player);
            }
        }
    
        return $i;
    }

    public function checkBets($player)
    {
        $i     = 1;
        $check = true;

        while ($i < 11):
            $iBet = $this->getBet($player, $i);

            if (
                $player->game->{"p{$i}name"} != ''
                && $this->getPot($player, $i) > 0
                && $iBet != 'FOLD'
            ):
                if ($iBet < $player->game->bet):
                    $check = false;
                endif;
            endif;
    
            $i++;
        endwhile;
    
        return $check;
    }

    public function checkPass($player)
    {
        return ($player->game->move == $this->lastBet($player) && $this->checkBets($player) && $this->gameStatus($player) == 'live') ? true : false;
    }

    public function calcBeta( $player, $string )
    {
        $array = explode('x', $string);
        $count = count($array);
    
        if ($count == 2)
            return $this->calcVarsBeta($player, $array[0]) * $this->calcVarsBeta($player, $array[1]);
    
        return 0;
    }

    public function calcVarsBeta( $player, $string )
    {
        list($smallBlinds, $bigBlinds) = $this->getBlinds($player);
    
        if (! preg_match('/[A-Za-z]/i', $string))
        {
            if (strpos($string, '.'))
                return floatval($string);
            else
                return intval($string);
        }
        
        if ($string === 'BB')
            return $bigBlinds;
        elseif ($string === 'SB')
            return $smallBlinds;
        elseif ($string === 'POT')
            return $player->game->pot;
        
        return 1;
    }

    public function gameStatus($player)
    {
        $np = $this->getNumberOfPlayers($player);
        $ai = $this->getAiPlayers($player);
        $fo = $this->getFoldedPlayers($player);
        $bu = $this->getBustedPlayers($player);

        $gameStatus = 'live';
        $allpl      = $np - $bu;
        $nfpl       = $allpl - $fo;
        $lipl       = $nfpl - $ai;

        if ($this->isInterplay($player) || $this->isStraddling($player))
        {
            if ($nfpl == 1 && $allpl > 1)
                $gameStatus = 'allfold';

            if ($lipl < 2 && $allpl > 1 && $this->checkBets($player) == true && $ai > 0)
                $gameStatus = 'showdown';
        }

        return $gameStatus;
    }

    public function getNumberOfPlayers($player, $seat = false)
    {
        $np = 0;

        for ($i = ($seat ? $seat : 1); $i < ($seat ? $seat + 1 : 11); $i++)
        {
            $plyr = $player->game->{"p{$i}name"};

            if (empty($plyr) || !isset($this->players[ $plyr ]) || $this->players[ $plyr ]->seat === false):
                continue;
            endif;

            $np++;
        }

        return $seat ? boolval($np) : $np;
    }

    public function getAiPlayers($player, $seat = false)
    {
        $ai = 0;

        for ($i = ($seat ? $seat : 1); $i < ($seat ? $seat + 1 : 11); $i++)
        {
            $plyr = $player->game->{"p{$i}name"};

            if (empty($plyr)):
                continue;
            endif;

            $seatPlayer = $this->players[ $plyr ];
            $seatFold   = substr($seatPlayer->bet, 0, 1);

            if (
                intval($seatPlayer->pot) == 0
                && intval($seatPlayer->bet) > 0
                && $seatFold != 'F'
                && ($player->game->hand > 4 && $player->game->hand < 15)
            )
                $ai++;
        }

        return $seat ? boolval($ai) : $ai;
    }

    public function getFoldedPlayers($player, $seat = false)
    {
        $fo = 0;

        for ($i = ($seat ? $seat : 1); $i < ($seat ? $seat + 1 : 11); $i++)
        {
            $plyr = $player->game->{"p{$i}name"};

            if (empty($plyr)):
                continue;
            endif;

            $seatPlayer = $this->players[ $plyr ];
            $seatFold   = substr($seatPlayer->bet, 0, 1);

            if ($seatFold == 'F' && intval($seatPlayer->pot) > 0 && ($player->game->hand > 4 && $player->game->hand < 15))
                $fo++;
        }

        return $seat ? boolval($fo) : $fo;
    }

    public function getBustedPlayers($player, $seat = false)
    {
        $bu = 0;

        for ($i = ($seat ? $seat : 1); $i < ($seat ? $seat + 1 : 11); $i++)
        {
            $plyr = $player->game->{"p{$i}name"};

            if (empty($plyr)):
                continue;
            endif;

            $seatPlayer = $this->players[ $plyr ];

            list($smallBlinds, $bigBlinds) = $this->getBlinds($seatPlayer);

            if (in_array($this->getBet($seatPlayer), [0, 'F']) && intval($seatPlayer->pot) < $bigBlinds)
                $bu++;
        }

        return $seat ? boolval($bu) : $bu;
    }

    /** */
    public function &getDependencies($data)
    {
        return $this->players[ $data->player ];
    }

    public function updateDependencies($data)
    {
        if ( !isset($data->player, $data->gameID) )
            return false;
        
        $gameID  = intval($data->gameID);
        $player  = $data->player;
        $refetch = false;

        if ( isset($this->games[ $gameID ]) && $this->games[ $gameID ] !== false ):
            $game  = &$this->games[ $gameID ];
            $query = $this->db->query("SELECT `lastupdated` FROM `" . DB_POKER . "` WHERE `gameID` = {$gameID}");

            if ( !$query->rowCount() ):
                $this->players[ $player ] = false;
                return false;
            endif;

            $fetch = $query->fetch(PDO::FETCH_OBJ);

            if ($fetch->lastupdated > $game->lastupdated):
                $refetch = true;
            endif;
        endif;
        
        if ( $refetch || !isset($this->games[ $gameID ]) || $this->games[ $gameID ] === false ):
            $query  = $this->db->query("SELECT * FROM " . DB_POKER . " WHERE gameID = {$gameID}");

            if ( $query->rowCount() ):
                $this->games[ $gameID ] = $query->fetch( PDO::FETCH_OBJ );
            else:
                $this->games[ $gameID ] = false;
            endif;
        endif;

        if ($this->games[ $gameID ] === false) {
            $this->players[ $player ] = false;
            return false;
        }

        $game  = &$this->games[ $gameID ];
        for ($i = 1; $i < 11; $i++):
            $seater = $game->{"p{$i}name"};

            if (empty($seater) || isset($this->players[$seater]) || $seater === $player):
                continue;
            endif;
            
            $this->players[ $seater ] = new stdClass();
            $this->players[ $seater ]->id   = $seater;
            $this->players[ $seater ]->game = &$this->games[ $gameID ];
            $this->players[ $seater ]->seat = $i;
            $this->players[ $seater ]->lastAct = 0;
            $this->players[ $seater ]->gamePass = false;
            $this->players[ $seater ]->pot = &$game->{"p{$i}pot"};
            $this->players[ $seater ]->bet = &$game->{"p{$i}bet"};
            $this->players[ $seater ]->cards = [&$game->{"p{$i}card1"}, &$game->{"p{$i}card2"}];

            if ($game->gamestyle === 'o'):
                $this->players[ $seater ]->cards[] = &$game->{"p{$i}card3"};
                $this->players[ $seater ]->cards[] = &$game->{"p{$i}card4"};
            endif;
    
            foreach ($this->players[ $seater ]->cards as $ci => $card):
                $this->players[ $seater ]->{'card' . ($ci + 1)} = &$card;
            endforeach;
    
            $this->players[ $seater ]->nofold_handsplayed = 0;
            $this->players[ $seater ]->nofold_handswon = 0;
            $this->players[ $seater ]->noleave_handsplayed = 0;
            $this->players[ $seater ]->noleave_handswon = 0;
            $this->players[ $seater ]->lastchips = 0;
            $this->players[ $seater ]->chipswon = 0;
            $this->players[ $seater ]->went_allin = false;
            $this->players[ $seater ]->allin_this_hand = false;
            $this->players[ $seater ]->cont_allin_count = 0;
            $this->players[ $seater ]->cont_allin_win_count = 0;

            $this->players[ $seater ]->isBot = \OPSAddon::isActive('bots') ? boolval($this->playerdata(['isbot'], $this->players[ $seater ])) : false;
        endfor;

        if (
            isset($this->players[$player])
            && $this->players[$player] !== false
        ):
            if (isset($data->gamepass) && $data->gamepass !== $this->players[$player]->gamePass):
                $playQ = $this->db->prepare("SELECT `gamepass` FROM " . DB_PLAYERS . " WHERE username = ? AND banned = 0 AND approve = 0");
                $playQ->execute([ $player ]);

                if ( !$playQ->rowCount() )
                    return false;

                $playF = $playQ->fetch(PDO::FETCH_OBJ);

                if ($playF->gamepass === $data->gamepass):
                    $this->players[$player]->gamePass = $data->gamepass;
                endif;
            endif;

            return false;
        endif;

        $playQ = $this->db->prepare("SELECT `gamepass` FROM " . DB_PLAYERS . " WHERE username = ? AND banned = 0 AND approve = 0");
        $playQ->execute([ $player ]);

        if ( !$playQ->rowCount() )
            return false;
        
        $game  = &$this->games[ $gameID ];
        $playF = $playQ->fetch(PDO::FETCH_OBJ);
        $seat  = false;
        $pot   = 0;
        $bet   = 0;
        $cards = [];

        for ($i = 1; $i < 11; $i++):
            $plyr = $game->{"p{$i}name"};

            if ($plyr !== $player)
                continue;
            
            $seat = $i;
            $pot  = &$game->{"p{$i}pot"};
            $bet  = &$game->{"p{$i}bet"};

            for ($j = 1; $j <= ($game->gamestyle === 'o' ? 4 : 2); $j++):
                $cards[] = &$game->{"p{$i}card{$j}"};
            endfor;
            break;
        endfor;

        $this->players[ $player ] = new stdClass();
        $this->players[ $player ]->id   = $player;
        $this->players[ $player ]->game = &$this->games[ $gameID ];
        $this->players[ $player ]->seat = &$seat;
        $this->players[ $player ]->gamePass = $playF->gamepass;
        $this->players[ $player ]->lastAct = 0;
        $this->players[ $player ]->pot = &$pot;
        $this->players[ $player ]->bet = &$bet;
        $this->players[ $player ]->cards = &$cards;

        foreach ($cards as $ci => $card):
            $this->players[ $player ]->{'card' . ($ci + 1)} = &$card;
        endforeach;

        $this->players[ $player ]->nofold_handsplayed = 0;
        $this->players[ $player ]->nofold_handswon = 0;
        $this->players[ $player ]->noleave_handsplayed = 0;
        $this->players[ $player ]->noleave_handswon = 0;
        $this->players[ $player ]->lastchips = 0;
        $this->players[ $player ]->chipswon = 0;
        $this->players[ $player ]->went_allin = false;
        $this->players[ $player ]->allin_this_hand = false;
        $this->players[ $player ]->cont_allin_count = 0;
        $this->players[ $player ]->cont_allin_win_count = 0;

        $this->players[ $player ]->isBot = \OPSAddon::isActive('bots') ? boolval($this->playerdata(['isbot'], $this->players[ $player ])) : false;
    }

    public function gamedata($data, $player, $hook = false)
    {
        if ( !is_array($data) ):
            return false;
        endif;

        $count = count($data);
        $game  = $this->games[ $player->game->gameID ];

        if (isset($data[0])):
            $map = array_map(function($key)
            {
                return $game->{$key};
            }, array_values($data));

            return $count === 1 ? $map[0] : $map;
        endif;

        foreach ($data as $key => $value):
            $game->{$key} = $value;
        endforeach;

        $this->games[ $player->game->gameID ] = $game;

        if ($hook):
            $sql = $this->addons->get_hooks(
                [
                    'content' => "UPDATE `" . DB_POKER . "` SET " . implode(',', array_map( function($key) { return "`{$key}` = ?"; }, array_keys($data) )) . " WHERE `gameID` = {$game->gameID}",
                    'wss'     => $this,
                    'player'  => $player,
                ],
                $hook
            );
        else:
            $sql = "UPDATE `" . DB_POKER . "` SET " . implode(',', array_map( function($key) { return "`{$key}` = ?"; }, array_keys($data) )) . " WHERE `gameID` = {$game->gameID}";
        endif;

        $prep = $this->db->prepare($sql);
        $prep->execute(array_values($data));

        return $count === 1 ? $data[array_key_first($data)] : $data;
    }

    public function statdata($data, $player, $hook = false)
    {
        if ( !is_array($data) ):
            return false;
        endif;

        $count = count($data);

        if (isset($data[0])):
            $sql = "SELECT " . implode(',', array_values($data)) . " FROM " . DB_STATS . " WHERE `player` = ?";

            if ($hook):
                $sql = $this->addons->get_hooks([
                    'content' => $sql,
                    'wss'     => $this,
                    'player'  => $player,
                ], $hook);
            endif;

            $que = $this->db->prepare($sql);
            $que->execute([ $player->id ]);

            if ( !$que->rowCount() ):
                return false;
            endif;

            $map = $que->fetch( PDO::FETCH_OBJ );

            return $count === 1 ? $map->{$data[0]} : $map;
        endif;

        $sql = "UPDATE " . DB_STATS . " SET " . implode(',', array_map(function($key) use ($data) { return substr($data[$key], 0, 3) === '::>' ? "`{$key}` = " . substr($data[$key], 3) : "`{$key}` = ?"; }, array_keys($data))) . " WHERE `player` = '{$player->id}'";

        if ($hook):
            $sql = $this->addons->get_hooks(['content' => $sql], $hook);
        endif;

        $que = $this->db->prepare($sql);
        $que->execute(array_values( array_filter($data, function($x){ return substr($x, 0, 3) === '::>' ? false : true; })));

        return $count === 1 ? $data[array_key_first($data)] : $data;
    }

    public function playerdata($data, $player, $hook = false)
    {
        if ( !is_array($data) ):
            return false;
        endif;

        if (is_object($player)):
            $playerId = $player->id;
            $key      = 'username';
        elseif (is_numeric($player)):
            $playerId = $player;
            $key      = 'ID';
        elseif (is_string($player)):
            $playerId = $player;
            $key      = 'username';
        endif;

        $count = count($data);

        if (isset($data[0])):
            $sql = "SELECT " . implode(',', array_values($data)) . " FROM " . DB_PLAYERS . " WHERE `{$key}` = ?";

            if ($hook):
                $sql = $this->addons->get_hooks(['content' => $sql], $hook);
            endif;

            $que = $this->db->prepare($sql);
            $que->execute([ $playerId ]);

            if ( !$que->rowCount() ):
                return '';
            endif;

            $map = $que->fetch( PDO::FETCH_OBJ );
            return $count === 1 ? $map->{$data[0]} : $map;
        endif;

        $sql = "UPDATE " . DB_PLAYERS . " SET " . implode(',', array_map(function($key) use ($data) { return substr($key, 0, 3) === '::>' ? "`{$key}` = " . substr($data[$key], 3) : "`{$key}` = ?"; }, array_keys($data))) . " WHERE `{$key}` = '{$playerId}'";

        if ($hook):
            $sql = $this->addons->get_hooks(['content' => $sql], $hook);
        endif;

        $que = $this->db->prepare($sql);
        $que->execute(array_values($data));

        return $count === 1 ? $data[array_key_first($data)] : $data;
    }

    public function kickPlayer($player, $exit = false)
    {
        $proceed = false;

        if ( !$player->seat ):
            $proceed = true;
            return false;
        endif;

        if ( !$proceed ):
            $addToBank = $this->addons->get_hooks(
                array(
                    'content' => ($player->game->tabletype !== 't' || $this->getNumberOfPlayers($player) === 1) ? true : false
                ),
                array(
                    'page'     => 'includes/inc_poker.php',
                    'location' => 'add_to_bank_bool',
                )
            );
    
            if ( $addToBank ):                
                $winpot = $this->addons->get_hooks(
                    array(
                        'content' => $player->pot,
                        'player'  => $player,
                    ),
                    array(
                        'page'     => 'includes/inc_poker.php',
                        'location' => 'winpot_var',
                    )
                );
                $statpotfield = $this->addons->get_hooks(
                    array(
                        'content' => 'winpot',
                        'table'   => $player->game
                    ),
                    array(
                        'page'     => 'includes/inc_poker.php',
                        'location' => 'stat_pot_field'
                    )
                );

                OPS_sitelog($player->id, sprintf( __( "%s left the table with %s. New bank amount: %s.", 'core' ), $player->id, money($player->pot, true, $this->moneyPrefix($player)), money($winpot, true, $this->moneyPrefix($player)) ));
                
                $this->statdata([$statpotfield => "::>{$statpotfield} + {$winpot}"], $player);
            endif;

            $this->gamedata(array_merge(
                [
                    "p{$player->seat}name" => '',
                    "p{$player->seat}pot"  => '',
                    "p{$player->seat}bet"  => '',
                ],
                $player->game->move == $player->seat ? [
                    'move' => $this->nextPlayer($player),
                ] : []
            ), $player);

            $proceed = true;
        endif;

        if ($proceed):
            $player->seat  = false;
            $player->pot   = 0;
            $player->bet   = 0;
            $player->card1 = false;
            $player->card2 = false;
            $player->cards = [];

            if (isset($player->card3))
                $player->card3 = false;
            
            if (isset($player->card4))
                $player->card4 = false;

            $player->startpot            = 0;
            $player->lastchips           = 0;
            $player->chipswon            = 0;
            $player->allin_this_hand     = false;
            $player->nofold_handsplayed  = 0;
            $player->noleave_handsplayed = 0;
        endif;

        return true;
    }

    /** */
    public function log($player, $message, $plObj = false, $updateGame = false)
    {
        $playerId = is_object($player) ? $player->id : strval($player);

        if (!empty($playerId) || is_object($plObj)):
            poker_log(
                $playerId,
                $message,
                $plObj ? $plObj->game->gameID : $player->game->gameID
            );
        endif;

        if ($updateGame) {
            $this->gamedata([
                'msg' => empty($player) ? $message : "{$playerId} {$message}"
            ], $plObj ? $plObj : $player);
        }

        $this->theme->addVariable('chatter', [
            'name' => $playerId
        ]);
        $this->theme->addVariable('message', $message);
        return $this->theme->viewPart('poker-log-message');
    }

    public function getBlinds($player, $og = false)
    {
        $blindmultiplier = ($player->game->tabletype === 't') ? (11 - ($this->getNumberOfPlayers($player) - $this->getBustedPlayers($player))) : 4;
        
        switch (intval($player->game->tablelimit))
        {
            case 25000:
                $tablemultiplier = 2;
                break;
            
            case 50000:
                $tablemultiplier = 4;
                break;
            
            case 100000:
                $tablemultiplier = 8;
                break;
            
            case 250000:
                $tablemultiplier = 20;
                break;
            
            case 500000:
                $tablemultiplier = 40;
                break;
            
            case 1000000:
                $tablemultiplier = 80;
                break;
            
            default:
                $tablemultiplier = 1;
                break;
        }

        $blindmultiplier = $this->addons->get_hooks(
            array(
                'content' => $blindmultiplier,
                'wss'     => $this,
                'player'  => $player
            ),
            array(
                'page'     => 'wss',
                'location' => 'blind_multiplier'
            )
        );
        $tablemultiplier = $this->addons->get_hooks(
            array(
                'content' => $tablemultiplier,
                'wss'     => $this,
                'player'  => $player
            ),
            array(
                'page'     => 'wss',
                'location' => 'table_multiplier'
            )
        );

        $oSB = ($player->game->sbamount != 0) ? $player->game->sbamount : (25 * $blindmultiplier * $tablemultiplier);
        $oBB = ($player->game->sbamount != 0) ? $player->game->bbamount : (50 * $blindmultiplier * $tablemultiplier);

        if ($og):
            return [$oSB, $oBB];
        endif;

        $smallBlinds = $this->addons->get_hooks(
            array(
                'content' => $oSB,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'wss',
                'location' => 'small_blind'
            )
        );
        $bigBlinds = $this->addons->get_hooks(
            array(
                'content' => $oBB,
                'wss'     => $this,
                'player'  => $player,
            ),
            array(
                'page'     => 'wss',
                'location' => 'big_blind'
            )
        );

        return [$smallBlinds, $bigBlinds];
    }

    public function nextPlayer($player, $seat = false)
    {
        $seat = $seat ? $seat : $player->seat;
        
        $i = $seat;
        $x = 0;

        while ($x < 10)
        {
            $i++;
            $y = $this->ots($i);

            if (empty($player->game->{'p' . $y . 'name'}) || !isset($this->players[ $player->game->{'p' . $y . 'name'} ])):
                $i = $y;
                $x++;
                continue;
            endif;

            $seatPlayer = $this->players[ $player->game->{'p' . $y . 'name'} ];

            if ( !$seatPlayer->seat ):
                $i = $y;
                $x++;
                continue;
            endif;

            if (
                (
                    $this->getPot($seatPlayer) > 0
                    || $this->getBet($seatPlayer) > 0
                )
                && $this->getBet($seatPlayer) != 'FOLD'
            )
            {
                return $y;
            }
    
            $i = $y;
            $x++;
        }
    
        return 0;
    }

    public function nextDealer($player)
    {
        $i = $player->game->dealer;
        $x = 0;
        $botActive = $this->isAddonActive('bots');

        while ($x < 10)
        {
            $i++;

            $y     = ots($i);
            $yName = $player->game->{'p' . $y . 'name'};
            $yPot  = $player->game->{'p' . $y . 'pot'};

            if (
                (
                    ! $botActive
                    && !empty($yName)
                    && $yPot > 0
                )
                || (
                    $botActive
                    && !empty($yName)
                    && !$this->playerdata(['isbot'], $yName)
                    && $yPot > 0
                )
            )
                return $y;
    
            $i = $y;
            $x++;
        }

        return 0;
    }

    public function ots($n)
    {
        if ($n == 11)
            $n = 1;
        elseif ($n == 0)
            $n = 10;
        
        return $n;
    }

    public function getPot($player, $seat = false)
    {
        $seat = $seat ? $seat : $player->seat;
        $pot  = isset($player->game->{'p' . $seat . 'pot'}) ? $player->game->{'p' . $seat . 'pot'} : '';

        return intval($pot);
    }

    public function getBet($player, $seat = false)
    {
        $seat = $seat ? $seat : $player->seat;
        $bet  = isset($player->game->{'p' . $seat . 'bet'}) ? $player->game->{'p' . $seat . 'bet'} : '';

        if (substr($bet, 0, 1) === 'F')
            $bet = substr($bet, 1, 1) != '' ? 'FOLD' : 0;
        
        return $bet;
    }

    public function getBetMath($player, $seat = false)
    {
        $seat = $seat ? $seat : $player->seat;
        $pbet = isset($player->game->{'p' . $seat . 'bet'}) ? $player->game->{'p' . $seat . 'bet'} : '';
        $bet = str_replace('F', '', $pbet);
        return intval($bet);
    }

    public function getStatPot($player, $seat = false)
    {
        $seat = $seat ? $seat : $player->seat;
        $seatplayer = $seat === $player->seat ? $player : $this->players[ $player->game->{"p{$seat}name"} ];
    
        return $this->statdata(['winpot'], $seatplayer);
    }

    public function &getPlayerBySeat($player, $seat)
    {
        return $this->getPlayerById( $player->game->{"p{$seat}name"} );
    }

    public function &getPlayerById($id)
    {
        return $this->players[ $id ];
    }

    public function isInterplay($player)
    {
        return $player->game->hand >= HAND_INTERPLAY_START && $player->game->hand <= HAND_INTERPLAY_END ? true : false;
    }

    public function isStraddling($player)
    {
        return $player->game->hand >= HAND_STRADDLE_START && $player->game->hand <= HAND_STRADDLE_END ? true : false;
    }

    public function isStraddle($player)
    {
        return defined('ISSTRADDLE') && ISSTRADDLE === 'yes' && $player->game->is_straddle == 1 ? true : false;
    }

    public function moneyPrefix($player)
    {
        return $this->addons->get_hooks(
            array(
                'content' => MONEY_PREFIX,
                'table'   => $player->game
            ),
            array(
                'page'     => 'general',
                'location' => 'money_prefix'
            )
        );
    }

    public function getPlayersData($player, $showCards = false)
    {
        $players = [];

        for ($i = 1; $i < 11; $i++):
            $plyr = $this->getPlayerData($player, $i, $showCards);

            if ( !$plyr )
                continue;

            $players[] = $plyr;
        endfor;

        return $players;
    }

    public function getPlayerData($player, $seat = false, $showCards = false)
    {
        $seat = $seat ? $seat : $player->seat;

        if (!is_numeric($seat) || $seat < 1)
            return false;

        $plyr  = $player->game->{"p{$seat}name"};
        $taken = false;
        $playr = [];

        if ( !empty($plyr) && isset($this->players[$plyr]) && $this->players[$plyr] !== false):
            $taken = true;
        endif;

        if ($taken):
            $opsTheme   = $this->theme;
            $game_style = $player->game->gamestyle;
            require __DIR__ . '/includes/card_sys.php';

            $seatPlayer = $this->players[$plyr];

            $this->theme->addVariable('player', array(
                'name'   	  => $seatPlayer->id,
                'pot'    	  => money($seatPlayer->pot, true, $this->moneyPrefix($seatPlayer)),
                'avatar' 	  => get_ava($seatPlayer->id),
                'before_name' => $this->addons->get_hooks(
                    array(
                        'player' => $player->id
                    ),
                    array(
                        'page'     => 'wss',
                        'location' => 'player_before_name'
                    )
                )
            ));
            $this->theme->addVariable('bet_money', money_small($seatPlayer->bet, true, $this->moneyPrefix($player)));

            $playr = [
                'seat'   => $seatPlayer->seat,
                'name'   => $seatPlayer->id,
                'pot'    => money($seatPlayer->pot),
                'bet'    => in_array($seatPlayer->bet, ['F', 0]) ? '' : $this->theme->viewPart('poker-player-chips'),
                'avatar' => $this->theme->viewPart('poker-player-avatar'),
                'info'   => $this->theme->viewPart('poker-player-info'),
                'cards'  => array_map(function ($card) use ($player, $seatPlayer, $playerCard) {
                    if ($player->game->hand < 4)
                        return '';

                    if ($player->game->hand < 12 && $seatPlayer->id !== $player->id)
                        return $playerCard['facedown-anim'];
                    
                    $dec = decrypt_card($card);
    
                    if ( !$dec )
                        return '';
                    
                    return $playerCard[ $dec . '-anim' ];
                }, $seatPlayer->cards),
            ];
        else:
            $showSeat = $this->addons->get_hooks(
                [
                    'content' => ($player->game->tabletype == 't' && $player->game->hand >= 0) ? false : true,
                    'wss'     => $this,
                    'player'  => $player,
                ],
                [
                    'page'     => 'wss',
                    'location' => 'show_seats_bool'
                ]
            );
            $this->theme->addVariable('seat_number', $seat);

            $playr = [
                'seat'   => $showSeat ? $seat : 0,
                'name'   => '',
                'pot'    => '',
                'bet'    => '',
                'avatar' => $showSeat ? $this->theme->viewPart('poker-player-circle') : '',
                'info'   => '',
                'cards'  => [],
            ];
        endif;

        if ($this->isAddonActive('bots')):
            $playr['bot'] = $taken ? boolval($this->playerdata(['isbot'], $plyr)) : false;
        endif;

        return $playr;
    }

    public function getTableData($player)
    {
        $opsTheme   = $this->theme;
        $game_style = $player->game->gamestyle;
        require __DIR__ . '/includes/card_sys.php';

        $cards  = [];
        $anim   = boolval($player->game->tableanim) ? '-anim' : '';
        $status = $this->gameStatus($player);

        switch (true):
            case ($player->game->hand > 9 || $status === 'showdown'):
                $cardRevealCount = 5;
                break;
            
            case $player->game->hand > 7:
                $cardRevealCount = 4;
                break;
            
            case $player->game->hand > 5:
                $cardRevealCount = 3;
                break;
            
            default:
                $cardRevealCount = 0;
                break;
        endswitch;

        if ($status === 'allfold'):
            $cardRevealCount = 0;
        endif;

        for ($i = 1; $i < 6; $i++):
            $card = $player->game->{"card{$i}"};

            if (empty($card) || $i > $cardRevealCount):
                $cards[$i] = $tableCard['facedown'];
                continue;
            endif;

            $dec = decrypt_card($card);
            if ( !$dec ):
                $cards[$i] = $tableCard['facedown'];
                continue;
            endif;

            $cards[$i] = $tableCard[$dec . $anim];
        endfor;

        $timeleft = ($player->game->lastmove + MOVETIMER) - time();
        $timeleft = $timeleft < 1 ? 0 : $timeleft;

        $rtn = [
            'addons'          => $this->activeAddons,
            'type'            => $player->game->tabletype,
            'tournament_type' => $player->game->tabletype === 't' ? $player->game->tournament_type : false,
            'lastmove'        => $player->game->lastmove,
            'move'            => $player->game->move,
            'dealer'          => $player->game->dealer,
            'hand'            => $player->game->hand,
            'pot'             => money($player->game->pot, true, $this->moneyPrefix($player)),
            'bet'             => $player->game->bet,
            'cards'           => $cards,
            'interplay'       => $player->game->hand > 4 && $player->game->hand < 12 ? true : false,
            'timeleft'        => $timeleft,
            'can_join'        => true,
        ];

        if ($this->isAddonActive('bots')):
            $rtn['botgod'] = $player->game->botgod;
        endif;

        if ($player->game->tabletype === 't' && $player->game->tournament_type === 'a' && $this->isAddonActive('tournament-advanced')):
            $startTime = strtotime($player->game->tadv_start_time);
            $rtn['can_join'] = time() > $startTime ? true : false;
        endif;

        return $rtn;
    }

    public function getDomChanges($player)
    {
        return $this->addons->get_hooks(
            [],
            [
                'page'     => 'wss',
                'location' => 'dom'
            ]
        );
    }

    public function isAddonActive($addon) {
        return in_array($addon, $this->activeAddons);
    }
}
