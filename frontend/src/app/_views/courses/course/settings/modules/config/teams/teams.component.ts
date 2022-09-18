import { Component, OnInit } from '@angular/core';
import {Team} from "../../../../../../../_domain/teams/team";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {ErrorService} from "../../../../../../../_services/error.service";
import {User} from "../../../../../../../_domain/users/user";
import {Reduce} from "../../../../../../../_utils/lists/reduce";
import {Course} from "../../../../../../../_domain/courses/course";
import {Order, Sort} from "../../../../../../../_utils/lists/order";

import _ from 'lodash';
import {exists} from "../../../../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";
import * as moment from "moment";

@Component({
  selector: 'app-teams',
  templateUrl: './teams.component.html',
  styleUrls: ['./teams.component.scss']
})
export class TeamsComponent implements OnInit {

  loading: boolean;
  loadingAction = false;
  maxMembersReached: boolean;

  nrTeamMembers: number;

  content: string;

  course: Course;
  courseID: number;
  courseFolder: string;

  teams: Team[]  = [];
  isTeamNameActive: boolean;
  teamMembers: User[];

  allUsers: User[];
  allUsersInTeams: User[];
  allNonMembers: User[];

  order = new Order();

  mode: 'add' | 'edit';

  newTeam: TeamData = {
    teamName: "",
    teamNumber: null,
    members: null,
    teamMembers: null,
    xp: null,
    level: null
  };
  teamToEdit: Team;
  teamToDelete: Team;

  importedFile: File;

  isTeamModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  isTestModalOpen: boolean;

  saving: boolean;

  filters: string[];
  orderBy = ['Name', 'Nickname', 'Student Number', 'Last Login'];

  selectUserQuery: string;
  selectedMembers: User[] = [];

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
      this.getCourseUsers(this.courseID);
      this.getIsTeamNameActive();
      this.getNrTeamMembers();
      this.getAllUsersInTeams(this.courseID);
      this.getAllNonMembers(this.courseID);
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

  getCourseUsers(courseId: number): void {

    this.api.getCourseUsers(courseId, "Student")
      .subscribe(users => {
          this.allUsers = users;
        },
        error => ErrorService.set(error))
  }

  getAllUsersInTeams(courseId: number): void {

    this.api.getAllUsersInTeams(courseId, "Student")
      .subscribe(users => {
          this.allUsersInTeams = users;

          this.order.active = { orderBy: this.orderBy[0], sort: Sort.ASCENDING };
          this.getCourseUsers(courseId);
        },
        error => ErrorService.set(error))
  }

  // getAllNonMembers
  getAllNonMembers(courseId: number): void {

    this.api.getAllNonMembers(courseId)
      .subscribe(users => {
          this.allNonMembers = users;
        },
        error => ErrorService.set(error))
  }
  getTeamMembers(teamId: number) {
    this.api.getTeamMembers(teamId)
      .subscribe(members => {
          this.teamMembers = members;
        },
        error => ErrorService.set(error))

    return this.teamMembers;
  }

  getIsTeamNameActive() {
    this.loading = true;
    this.api.getIsTeamNameActive(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        isTeamNameActive => this.isTeamNameActive = isTeamNameActive,
        error => ErrorService.set(error)
      );
  }

  getNrTeamMembers() {
    this.loading = true;
    this.api.getNrTeamMembers(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        nrTeamMembers => this.nrTeamMembers = nrTeamMembers,
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
        this.selectedMembers = null;
        this.loading = false;
      }) )
      .subscribe(() => {
        this.getTeams();
        const successBox = $('#action_completed');
        successBox.empty();
        successBox.append("New Team created");
        successBox.show().delay(3000).fadeOut();
      },
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

  addTeamMember(user: User): void {
    if (!this.selectedMembers) this.selectedMembers = [];
    if (!this.newTeam.teamMembers) this.newTeam.teamMembers = [];

    if(this.selectedMembers.length == 3) this.maxMembersReached = true;
    else {
      if (!this.selectedMembers.find(el => el.id === user.id)) {
        this.selectedMembers.push(user);

        const index = this.allNonMembers.findIndex(el => el.id === user.id);
        this.allNonMembers.splice(index, 1);

        this.newTeam.teamMembers.push(user)

        if (this.newTeam.teamMembers.length == 1){
          this.newTeam.members = (user.id).toString()
        } else {
          this.newTeam.members += '|' + (user.id).toString()
        }
      }
    }

  }
  removeMember(user: User): void {
    const index = this.selectedMembers.findIndex(el => el.id === user.id);
    this.allNonMembers.push(user);
    this.selectedMembers.splice(index, 1);

    const index2 = this.newTeam.teamMembers.findIndex(el => el.id === user.id);
    this.newTeam.teamMembers.splice(index2, 1);

    this.newTeam.members = '';
    if (this.newTeam.teamMembers.length == 1){
      this.newTeam.members = (user.id).toString()
    } else {
      this.newTeam.members += '|' + (user.id).toString()
    }

    if(this.newTeam.teamMembers.length < 3) this.maxMembersReached = false;
  }


  importTeams(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedTeams = reader.result;
      this.api.importCourseTeams(this.courseID, {file: importedTeams, replace})
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loadingAction = false;
        }) )
        .subscribe(
          nTeams => {
            this.getTeams();
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nTeams + " Teams" + (nTeams > 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error),
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportAllTeams() {
    // TODO
  }

  getFileContent(replace: boolean) {
    this.loadingAction = true;
    this.isTestModalOpen = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedTeams = reader.result;
      this.api.getFileContent(this.courseID, {file: importedTeams, replace})
        .pipe( finalize(() => {
          this.loadingAction = false;
          this.isTestModalOpen = false;
        }) )
        .subscribe(
          content => {
            this.content = content;
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(content);
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error),
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initItem(type: 'team', item?: any): void {
    if (type === 'team') {
      this.newTeam = {
        teamName: item?.teamName || "",
        teamNumber: item?.teamNumber || null,
        members: item?.members || null,
        teamMembers: item?.teamMembers || null,
        xp: item?.xp || null,
        level: item?.level || null
      };
      if (this.mode === 'edit') this.newTeam.id = item.id;
      this.teamToEdit = item;
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
    //if (type === 'team' && this.newTeam && this.newTeam.teamMembers) return (isValid(this.newTeam.teamName) )  ;
    return true;
  }

  onCSVFileSelected(files: FileList): void {
      this.importedFile = files.item(0);
  }


}

export interface TeamData {
  id?: number,
  teamName: string,
  teamNumber: number,
  members: string,
  teamMembers: User[],
  xp: number,
  level: number
}

/*  export interface TeamsConfigVars {
  maxMembers: number,
  isTeamNameActive: boolean,
}*/

export interface ImportTeamsData {
  file: string | ArrayBuffer,
  replace: boolean
}
