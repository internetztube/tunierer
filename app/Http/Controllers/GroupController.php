<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Group;
use App\Models\Team;
use App\Models\Game;

class GroupController extends Controller
{

    public function edit(Tournament $tournament, Group $group = null) {
        return View('group.edit', ['tournament' => $tournament, 'group' => $group]);
    }

    public function updateInsert(Tournament $tournament, Group $group = null) {
        $this->validate(request(), [
            'name' => 'required|max:255',
            'start_date' => 'required|date',
        ]);

        if(!$group) {
            $group = new Group();
        }

        $group->fill(request()->all());
        $group->tournament_id = $tournament->id;

        $groupWithSameName = Group::where('name', $group->name)
            ->where('tournament_id', $tournament->id)
            ->get()
            ->first();

        if($groupWithSameName) {
            if($groupWithSameName->id != $group->id) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        sprintf('Gruppe mit dem Namen "%s" existiert bereits.', $group->name)
                    ]);
            }
        }

        $group->save();

        return redirect('/tournament/detail/' . $tournament->id);
    }

    public function detail(Group $group) {

        return View('group.detail', [
            'group' => $group,
            'addAbleTeams' => $this->allAddAbleTeams($group),
            'teamSizes' => Group::teamSizes(),
            'teamSchema' => $group->schema,
        ]);
    }

    public function delete(Group $group) {
        $group->teams()->detach();
        $group->delete();
        return redirect()->back();
    }

    public function addTeam(Group $group, Team $team) {
        if($group->teams || count($group->teams->where('id', $team->id) < 1)) {
            $group->teams()->attach($team->id);
        }
        return redirect()->back();
    }

    public function removeTeam(Group $group, Team $team) {
        $group->teams()->detach($team->id);
        return redirect()->back();
    }
    
    public function schema(Group $group) {
        // dd($group->schema);
        return View('group.schema', [
            'group' => $group,
        ]);
    }
    
    public function updateSchema(Group $group) {
        $schema = request()->get('schema');

        foreach($schema as $key => $value) {
            $schema[$key] = (int) $value;
        }

        $schema = array_chunk($schema, 2, false);
        $group->schema = $schema;
        $group->save();
        return redirect('/group/detail/' . $group->id);
    }

    protected function allAddAbleTeams(Group $givenGroup) {
        $tournamentTeams = collect();

        foreach($givenGroup->tournament->groups as $group) {
            foreach($group->teams as $team) {
                $tournamentTeams->push($team);
            }
        }

        $allTeams = Team::all();
        $addAbleTeams = collect();

        $found = false;
        foreach($allTeams as $allTeam) {
            foreach($tournamentTeams as $tournamentTeam) {
                if($tournamentTeam->id == $allTeam->id) {
                    $found = true;
                }
            }
            if(!$found) {
                $addAbleTeams->push($allTeam);
            }
            $found = false;
        }
        return $addAbleTeams;
    }
}