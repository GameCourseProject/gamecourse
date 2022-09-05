import { Component, OnInit } from '@angular/core';
import {Team} from "../../../../../../../_domain/teams/team";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {exists} from "../../../../../../../_utils/misc/misc";

@Component({
  selector: 'app-teams',
  templateUrl: './teams.component.html',
  styleUrls: ['./teams.component.scss']
})
export class TeamsComponent implements OnInit {

  loading: boolean;

  courseID: number;
  courseFolder: string;
  teams: Team[]  = [];

  mode: 'add' | 'edit';

  newTeam: TeamData = {
    name: "",
    number: null,
    members: null,
    xp: null
  };
  teamToEdit: Team;
  teamToDelete: Team;

  isTeamModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  selectedMember: number = null;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public router: Router

  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getTeams();
    });
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getTeams() {
    this.loading = true;
    this.api.getTeams(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        teams => this.teams = teams,
        error => ErrorService.set(error)
      );
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  createTeam(): void{
    this.loading = true;

    this.api.createTeam(this.courseID, this.newTeam)
      .pipe( finalize(() => {
        this.isTeamModalOpen = false;
        this.clearObject(this.newTeam);
        this.selectedMember = null;
        this.loading = false;
      }) )
      .subscribe(
        res => this.getTeams(),
        error => ErrorService.set(error)
      )
  }

  editTeam(): void{
    this.loading = true;
    this.newTeam['id'] = this.teamToEdit.id;

    this.api.editTeam(this.courseID, this.newTeam)
      .pipe( finalize(() => {
        this.isTeamModalOpen = false;
        this.clearObject(this.newTeam);
        this.selectedMember = null;
        this.loading = false;
      }) )
      .subscribe(
        res => this.getTeams(),
        error => ErrorService.set(error)
      )
  }

  deleteTeam(): void {
    this.loading = true;
    this.api.deleteTeam(this.courseID, this.teamToDelete.id)
      .pipe( finalize(() => {
        this.teamToDelete = null;
        this.isDeleteVerificationModalOpen = false;
        this.loading = false
      }) )
      .subscribe(
        res => this.getTeams(),
        error => ErrorService.set(error)
      )
  }

  addTeamMember(member: number){
    if (!this.newTeam.members) this.newTeam.members = member.toString();
    else this.newTeam.members += ' | ' + member.toString();
  }

  exportAllTeams() {
    // TODO
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initItem(type: 'team', item?: any): void {
    if (type === 'team') {
      this.newTeam = {
        name: item?.name || "",
        number: item?.number || null,
        members: item?.members || null,
        xp: item?.xp || null
      };
      if (this.mode === 'edit') this.newTeam.id = item.id;
      this.teamToDelete = item;
    }
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  isReadyToSubmit(type: 'team') {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    if (type === 'team') return isValid(this.newTeam.name) && isValid(this.newTeam.number);
    // && isValid(this.newSkill.description); FIXME
    return true;
  }

  getCourseStudents(id: number){

  }

}

export interface TeamData {
  id?: number,
  name: string,
  number: number,
  members: string,
  xp: number
}
