import { Component, OnInit } from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../_services/error.service";
import {ImageUpdateService} from "../../../../_services/image-update.service";

import {User} from "../../../../_domain/User";
import {Course} from "../../../../_domain/Course";
import {Role} from "../../../../_domain/Role";
import {AuthType} from 'src/app/_domain/AuthType';
import {UserData} from "../../../my-info/my-info/my-info.component";

import {ImageManager} from "../../../../_utils/image-manager";
import {Ordering} from "../../../../_utils/ordering";
import {DownloadManager} from "../../../../_utils/download-manager";

import _ from 'lodash';

@Component({
  selector: 'app-users',
  templateUrl: './users.component.html',
  styleUrls: ['./users.component.scss']
})
export class UsersComponent implements OnInit {

  loading = true;
  loadingAction = false;

  user: User;
  course: Course;
  roles: Role[];

  allUsers: User[];
  filteredUsers: User[];

  allNonUsers: User[];
  filteredNonUsers: User[];

  searchQuery: string;

  filters: string[];
  filtersActive: string[];

  orderBy = ['Name', 'Nickname', 'Student Number', 'Last Login'];
  orderByActive: {orderBy: string, sort: number};
  DEFAULT_SORT = 1;

  originalPhoto: string;  // Original photo
  photoToAdd: File;       // Any photo that comes through the input
  photo: ImageManager;    // Photo to be displayed

  importedFile: File;

  isNewUserMethodModal: boolean;
  isUserModalOpen: boolean;
  isSelectUserModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  mode: 'add' | 'edit';
  newUser: UserData = {
    name: null,
    nickname: null,
    studentNumber: null,
    email: null,
    major: null,
    isAdmin: null,
    isActive: null,
    auth: null,
    username: null,
    roles: null
  };
  userToEdit: User;
  userToDelete: User;

  selectUserQuery: string;
  selectedUserRole: string = null;
  selectedUserRoles: string[];
  selectedUsers: User[];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private photoUpdate: ImageUpdateService
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getLoggedUser();
    this.route.params.subscribe(params => {
      this.getCourse(params.id);
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getLoggedUser(): void {
    this.api.getLoggedUser()
      .subscribe(
        user => this.user = user,
        error => ErrorService.set(error)
      );
  }

  getCourse(courseId: number): void {
    this.api.getCourse(courseId)
      .subscribe(course => {
        this.course = course;
        this.getCourseRoles(courseId);
      },
        error => ErrorService.set(error));
  }

  getCourseRoles(courseId: number): void {
    this.api.getCourseRoles(courseId)
      .subscribe(
        roles => {
          this.roles = roles;
          this.filters = roles.map(role => role.name);
          this.getCourseUsers(courseId);
        },
        error => ErrorService.set(error));
  }

  getCourseUsers(courseId: number): void {
    this.api.getCourseUsers(courseId)
      .subscribe(users => {
        this.allUsers = users;
        this.filteredUsers = _.cloneDeep(users); // deep copy

        this.filtersActive = _.cloneDeep(this.filters);
        this.orderByActive = { orderBy: this.orderBy[0], sort: this.DEFAULT_SORT };
        this.reduceList();

        this.getNonCourseUsers(courseId);
      },
        error => ErrorService.set(error))
  }

  getNonCourseUsers(courseId: number): void {
    this.api.getNotCourseUsers(courseId)
      .subscribe(users => {
        this.allNonUsers = users;
        this.filteredNonUsers = _.cloneDeep(users); // deep copy

        this.loading = false;
      },
        error => ErrorService.set(error))
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  onSearch(query: string): void {
    this.searchQuery = query;
    this.reduceList();
  }

  onFilterChanged(filter: {filter: string, state: boolean}): void {
    if (filter.state) {
      this.filtersActive.push(filter.filter);

    } else {
      const index = this.filtersActive.findIndex(el => el === filter.filter);
      this.filtersActive.splice(index, 1);
    }

    this.reduceList();
  }

  onOrderByChanged(order: {orderBy: string, sort: number}): void {
    this.orderByActive = order;
    this.orderList();
  }

  reduceList(): void {
    this.filteredUsers = [];

    this.allUsers.forEach(user => {
      if (this.isQueryTrueSearch(user, this.searchQuery) && this.isQueryTrueFilter(user))
        this.filteredUsers.push(user);
    });

    this.orderList();
  }

  orderList(): void {
    switch (this.orderByActive.orderBy) {
      case "Name":
        this.filteredUsers.sort((a, b) => Ordering.byString(a.name, b.name, this.orderByActive.sort))
        break;

      case "Nickname":
        this.filteredUsers.sort((a, b) => Ordering.byString(a.nickname, b.nickname, this.orderByActive.sort))
        break;

      case "Student Number":
        this.filteredUsers.sort((a, b) => Ordering.byNumber(a.studentNumber, b.studentNumber, this.orderByActive.sort))
        break;

      case "Last Login":
        this.filteredUsers.sort((a, b) => Ordering.byDate(a.lastLogin, b.lastLogin, this.orderByActive.sort))
        break;
    }
  }

  onSearchUser(): void {
    this.filteredNonUsers = [];

    this.allNonUsers.forEach(user => {
      if (this.isQueryTrueSearch(user, this.selectUserQuery) && !this.selectedUsers.find(el => el.id === user.id))
        this.filteredNonUsers.push(user);
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleActive(userID: number) {
    this.loadingAction = true;

    const user = this.allUsers.find(user => user.id === userID);
    user.isActive = !user.isActive;

    this.api.setCourseUserActive(this.course.id, user.id, user.isActive)
      .subscribe(
        res => this.loadingAction = false,
        error => ErrorService.set(error)
      );
  }

  async createUser(): Promise<void> {
    this.loadingAction = true;

    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.newUser.roles = this.selectedUserRoles || [];

    this.api.createCourseUser(this.course.id, this.newUser)
      .subscribe(
        () => this.getCourseUsers(this.course.id),
        error => ErrorService.set(error),
        () => {
          this.isUserModalOpen = false;
          this.clearObject(this.newUser);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New user created");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  async editUser(): Promise<void> {
    this.loadingAction = true;
    this.newUser['id'] = this.userToEdit.id;

    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.newUser.roles = this.selectedUserRoles || [];

    this.api.editCourseUser(this.course.id, this.newUser)
      .subscribe(
        () => {
          this.getCourseUsers(this.course.id);
          if (this.user.id === this.newUser.id && this.newUser.image)
            this.photoUpdate.triggerUpdate(); // Trigger change on navbar
        },
        error => ErrorService.set(error),
        () => {
          this.isUserModalOpen = false;
          this.clearObject(this.newUser);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: " + this.userToEdit.name + " edited");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  submitUsers(): void {
    this.api.addUsersToCourse(this.course.id, this.selectedUsers, this.selectedUserRole)
      .subscribe(() => this.getCourseUsers(this.course.id),
        error => ErrorService.set(error),
        () => {
          this.isSelectUserModalOpen = false;
          this.selectedUserRole = null;
          this.selectedUsers = null;
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New User(s) added");
          successBox.show().delay(3000).fadeOut();
        })
  }

  deleteUser(user: User): void {
    this.loadingAction = true;
    this.api.deleteCourseUser(this.course.id, user.id)
      .subscribe(
        () => {
          const index = this.allUsers.findIndex(el => el.id === user.id);
          this.allUsers.splice(index, 1);
          this.reduceList();
          this.getNonCourseUsers(this.course.id);
        },
        error => ErrorService.set(error),
        () => {
          this.isDeleteVerificationModalOpen = false;
          this.loadingAction = false
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: " + user.name  + ' - ' + user.studentNumber + " removed from this course");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  importUsers(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedUsers = reader.result;
      this.api.importCourseUsers(this.course.id, {file: importedUsers, replace})
        .subscribe(
          nUsers => {
            this.getCourseUsers(this.course.id);
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nUsers + " Users" + (nUsers > 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error),
          () => {
            this.isImportModalOpen = false;
            this.loadingAction = false;
          }
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportUsers(): void {
    this.saving = true;

    this.api.exportCourseUsers(this.course.id)
      .subscribe(
        contents => DownloadManager.downloadAsCSV('Users - ' + this.course.name + ' ' + this.course.year, contents),
        error => ErrorService.set(error),
        () => this.saving = false
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  parseForSearching(query: string): string[] {
    let res: string[];
    let temp: string;
    query = query.swapPTChars();

    res = query.toLowerCase().split(' ');

    temp = query.replace(' ', '').toLowerCase();
    if (!res.includes(temp)) res.push(temp);

    temp = query.toLowerCase();
    if (!res.includes(temp)) res.push(temp);
    return res;
  }

  isQueryTrueSearch(user: User, query: string): boolean {
    return !query ||
      (user.name && !!this.parseForSearching(user.name).find(a => a.includes(query.toLowerCase()))) ||
      (user.nickname && !!this.parseForSearching(user.nickname).find(a => a.includes(query.toLowerCase()))) ||
      (user.studentNumber && !!this.parseForSearching(user.studentNumber.toString()).find(a => a.includes(query.toLowerCase()))) ||
      (user.email && !!this.parseForSearching(user.email).find(a => a.includes(query.toLowerCase()))) ||
      (user.username && !!this.parseForSearching(user.username).find(a => a.includes(query.toLowerCase())));
  }

  isQueryTrueFilter(user: User): boolean {
    if (this.filters.length === 0)
      return true;

    for (const filter of this.filtersActive) {
      if (user.roles.find(role => role.name === filter))
        return true;
    }
    return false;
  }

  isReadyToSubmit() {
    let isValid = function (text) {
      return (text != "" && text != undefined)
    }

    // Validate inputs
    return isValid(this.newUser.name) && isValid(this.newUser.studentNumber) && isValid(this.newUser.email) &&
      isValid(this.newUser.major) && isValid(this.newUser.auth) && isValid(this.newUser.username) && this.selectedUserRoles?.length > 0;
  }

  initEditUser(user: User): void {
    this.newUser = {
      name: user.name,
      nickname: user.nickname,
      studentNumber: user.studentNumber,
      email: user.email,
      major: user.major,
      isAdmin: user.isAdmin,
      isActive: user.isActive,
      auth: user.authMethod,
      username: user.username,
    };
    this.userToEdit = user;
    this.photo.set(user.photoUrl);
    this.selectedUserRoles = this.userToEdit.roles.map(role => role.name);
  }

  onFileSelected(files: FileList, type: 'image' | 'file'): void {
    if (type === 'image') {
      this.photoToAdd = files.item(0);
      this.photo.set(this.photoToAdd);

    } else {
      this.importedFile = files.item(0);
    }
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  addUser(user: User): void {
    if (!this.selectedUsers) this.selectedUsers = [];

    if (!this.selectedUsers.find(el => el.id === user.id)) {
      this.selectedUsers.push(user);
      const index = this.filteredNonUsers.findIndex(el => el.id === user.id);
      this.onSearchUser();
    }
  }

  removeUser(userID: number): void {
    const index = this.selectedUsers.findIndex(el => el.id === userID);
    this.selectedUsers.splice(index, 1);
    this.onSearchUser();
  }

  addRole(role: string): void {
    if (!this.selectedUserRoles) this.selectedUserRoles = [];

    if (!this.selectedUserRoles.find(el => el === role))
      this.selectedUserRoles.push(role);
  }

  removeRole(role: string): void {
    const index = this.selectedUserRoles.findIndex(el => el === role);
    this.selectedUserRoles.splice(index, 1);
  }

  filterRoles(): Role[] {
    if (!this.selectedUserRoles) return this.roles;
    return this.roles.filter(el => !this.selectedUserRoles.includes(el.name));
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

}