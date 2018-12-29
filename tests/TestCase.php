<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Xoco70\LaravelTournaments\Models\Championship;
use Xoco70\LaravelTournaments\Models\Fight;
use Xoco70\LaravelTournaments\Models\FightersGroup;
use Xoco70\LaravelTournaments\Models\Tournament;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;

    protected $root;
    protected $baseUrl = 'http://tournament-plugin.test';

    protected $settings;
    protected $users;
    protected $championshipWithComp;
    protected $championshipWithTeam;


    protected function getChampionship($isTeam)
    {
        $isTeam
            ? $championship = $this->championshipWithTeam
            : $championship = $this->championshipWithComp;

        return $championship;
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->root = new User();
        parent::setUp();
        $this->tournament = Tournament::with(
            'competitors',
            'teams',
            'championshipSettings'
        )->first();

        $this->championshipWithComp = Championship::with(
            'teams', 'users', 'category', 'settings', 'fightersGroups.fights'
        )
            ->find($this->tournament->championships[0]->id);
        $this->championshipWithTeam = Championship::with(
            'teams', 'users', 'category', 'settings', 'fightersGroups.fights'
        )
            ->find($this->tournament->championships[1]->id);
    }

    public function generateTreeWithUI($setting)
    {
        return $this->call('POST', '/championships/' . $this->getChampionship($setting->isTeam)->id . '/trees', $setting->toArray());
    }

    /**
     * @param $setting
     * @param $numGroupsExpected
     * @param $currentTest
     */
    protected function checkGroupsNumber($setting, $numGroupsExpected, $currentTest)
    {
        $count = FightersGroup::where('championship_id', $setting->championship->id)
            ->where('round', 1)
            ->count();

        if ((int)($setting->numFighters / $setting->fightingAreas) <= 1) {
            $this->assertTrue($count == 0);

            return;
        }
        $expected = $numGroupsExpected[$setting->numFighters - 1];
        if ($count != $expected) {
            dd(
                ['Method' => $currentTest,
                    'championship' => $setting->championship->id,
                    'NumCompetitors' => $setting->numFighters,
                    'preliminaryGroupSize' => $setting->preliminaryGroupSize,
                    'NumArea' => $setting->fightingAreas,
                    'isTeam' => $setting->isTeam,
                    'Real' => $count,
                    'Excepted' => $expected,
                    'numGroupsExpected[' . ($setting->numFighters - 1) . ']' => $numGroupsExpected[$setting->numFighters - 1] . ' / ' . $setting->fightingAreas,
                ]
            );
        }
        $this->assertTrue($count == $expected);
    }

    /**
     * @param $setting
     * @param $numFightsExpected
     * @param $methodName
     */
    protected function checkFightsNumber($setting, $numFightsExpected, $methodName)
    {
        $groupSize = $setting->hasPreliminary ? $setting->preliminaryGroupSize : 2;
        $count = $this->getFightsCount($setting->championship_id); // For round 1

        if ((int)($setting->numFighters / $setting->fightingAreas) <= 1
            || $setting->numFighters / ($groupSize * $setting->fightingAreas) < 1) {
            $this->assertTrue($count == 0);

            return;
        }

        if ($count != $numFightsExpected) {
            dd(['Method' => $methodName,
                'numFighters' => $setting->numFighters,
                'NumArea' => $setting->fightingAreas,
                'groupSize' => $groupSize,
                'Real' => $count,
                'isTeam' => $setting->isTeam,
                'Excepted' => $numFightsExpected,
            ]);
        }
        $this->assertTrue($count == $numFightsExpected);
    }

    /**
     * @param $championshipId
     *
     * @return int
     */
    protected function getFightsCount($championshipId)
    {
        $groupsId = FightersGroup::where('championship_id', $championshipId)
            ->where('round', 1)
            ->select('id')
            ->pluck('id')->toArray();
        $count = Fight::whereIn('fighters_group_id', $groupsId)->count();

        return $count;
    }
}
