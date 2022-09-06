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



@Component({
  selector: 'app-teams',
  templateUrl: './teams.component.html',
  styleUrls: ['./teams.component.scss']
})
export class TeamsComponent implements OnInit {

  loading: boolean;

  course: Course;

  courseID: number;
  courseFolder: string;
  teams: Team[]  = [];

  allUsers: User[];
  teamMembers: User[];

  reduce = new Reduce();
  order = new Order();

  reduceUsers = new Reduce();

  mode: 'add' | 'edit';

  newTeam: TeamData = {
    teamName: "",
    teamNumber: null,
    members: null,
    xp: null
  };
  teamToEdit: Team;
  teamToDelete: Team;

  isTeamModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;


  filters: string[];
  orderBy = ['Name', 'Nickname', 'Student Number', 'Last Login'];

  selectUserQuery: string;
  selectedMembers: User[] = [];
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
      this.getCourseUsers(this.courseID);
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

    this.api.getCourseUsers(courseId)
      .subscribe(users => {
          this.allUsers = users;

          this.order.active = { orderBy: this.orderBy[0], sort: Sort.ASCENDING };
          this.reduceList(undefined, _.cloneDeep(this.filters));

        },
        error => ErrorService.set(error))
  }

  getTeamMember(teamId: number): void {

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

  addTeamMember(user: User): void {
    if (!this.selectedMembers) this.selectedMembers = [];

    if (!this.selectedMembers.find(el => el.id === user.id)) {
      this.selectedMembers.push(user);
      const index = this.allUsers.findIndex(el => el.id === user.id);
      this.allUsers.splice(index, 1);
      this.reduceListUsers();
    }

    if (this.selectedMembers.length == 1){
      this.newTeam.members = (user.id).toString()
    } else {
      this.newTeam.members += '|' + (user.id).toString()
    }
  }

  removeTeamMember(userID: number): void {
    const index = this.selectedMembers.findIndex(el => el.id === userID);
    this.allUsers.push(this.selectedMembers[index]);
    this.selectedMembers.splice(index, 1);
    this.reduceListUsers();
  }

  exportAllTeams() {
    // TODO
  }

  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string, filters?: string[]): void {
    this.reduce.searchAndFilter(this.allUsers, query, filters);
    this.orderList();
  }

  reduceListUsers(query?: string): void {
    this.reduceUsers.search(this.allUsers, query);
  }

  orderList(): void {
    switch (this.order.active.orderBy) {
      case "Name":
        this.reduce.items.sort((a, b) => Order.byString(a.name, b.name, this.order.active.sort))
        break;

      case "Nickname":
        this.reduce.items.sort((a, b) => Order.byString(a.nickname, b.nickname, this.order.active.sort))
        break;

      case "Student Number":
        this.reduce.items.sort((a, b) => Order.byNumber(a.studentNumber, b.studentNumber, this.order.active.sort))
        break;

      case "Last Login":
        this.reduce.items.sort((a, b) => Order.byDate(a.lastLogin, b.lastLogin, this.order.active.sort))
        break;
    }
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
    if (type === 'team') return (isValid(this.newTeam.teamName) && this.selectedMembers.length !== 0 ) || this.selectedMembers.length !== 0 ;
    return true;
  }


}

export interface TeamData {
  id?: number,
  teamName: string,
  teamNumber: number,
  members: string,
  xp: number
}
