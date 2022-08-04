import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../../../_services/update.service";

import {User} from "../../../../../_domain/users/user";
import {CourseUser} from "../../../../../_domain/users/course-user";
import {Course} from "../../../../../_domain/courses/course";
import {Role} from "../../../../../_domain/roles/role";
import {AuthType} from 'src/app/_domain/auth/auth-type';

import {ResourceManager} from "../../../../../_utils/resources/resource-manager";
import {Order, Sort} from "../../../../../_utils/lists/order";
import {DownloadManager} from "../../../../../_utils/download/download-manager";
import {Reduce} from "../../../../../_utils/lists/reduce";

import {exists} from "../../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";
import {environment} from "../../../../../../environments/environment";

@Component({
  selector: 'app-users',
  templateUrl: './users.component.html',
  styleUrls: ['./users.component.scss']
})
export class UsersComponent implements OnInit {

  loading = true;
  loadingAction = false;

  course: Course;
  rolesNames: string[];

  courseUsers: CourseUser[];
  nonCourseUsers: User[];

  reduce = new Reduce();
  reduceNonUsers = new Reduce();
  order = new Order();

  filters: string[];
  orderBy = ['Name', 'Nickname', 'Student Number', 'Last Activity' +
  ''];

  originalPhoto: string;  // Original photo
  photoToAdd: File;       // Any photo that comes through the input
  photo: ResourceManager; // Photo to be displayed

  importedFile: File;

  isNewUserMethodModal: boolean;
  isUserModalOpen: boolean;
  isSelectUserModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  mode: 'add' | 'edit';
  newCourseUser: CourseUserData = {
    name: null,
    nickname: null,
    studentNumber: null,
    email: null,
    major: null,
    auth: null,
    username: null,
    image: null
  };
  userToEdit: CourseUser;
  userToDelete: CourseUser;

  selectUserQuery: string;
  selectedUserRole: string;
  selectedUserRoles: string[];
  selectedUsers: User[] = [];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ResourceManager(sanitizer);
  }

  ngOnInit(): void {
    this.route.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getCourseRolesNames(courseID);
      await this.getCourseUsers(courseID);
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getCourseRolesNames(courseID: number): Promise<void> {
    this.rolesNames = await this.api.getRoles(courseID).toPromise() as string[];
    this.filters = this.rolesNames;
  }

  async getCourseUsers(courseID: number): Promise<void> {
    this.courseUsers = await this.api.getCourseUsers(courseID).toPromise();
    this.order.active = { orderBy: this.orderBy[0], sort: Sort.ASCENDING };
    this.reduceList('course-users', undefined, [...this.filters]);
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  reduceList(list: 'course-users' | 'non-course-users', query?: string, filters?: string[]): void {
    if (list === 'course-users') this.reduce.searchAndFilter(this.courseUsers, query, filters);
    else this.reduceNonUsers.search(this.nonCourseUsers, query);

    this.orderList(list);
  }

  orderList(list: 'course-users' | 'non-course-users'): void {
    if (list === 'non-course-users') {
      this.reduceNonUsers.items.sort((a, b) => Order.byString(a.name, b.name, Sort.ASCENDING));
      return;
    }

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

      case "Last Activity":
        this.reduce.items.sort((a, b) => Order.byDate(a.lastLogin, b.lastLogin, this.order.active.sort))
        break;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async createUser(): Promise<void> {
    this.loadingAction = true;

    if (this.photoToAdd)
      await ResourceManager.getBase64(this.photoToAdd).then(data => this.newCourseUser.image = data);

    this.newCourseUser.roles = this.selectedUserRoles;

    this.api.createCourseUser(this.course.id, this.newCourseUser)
      .pipe( finalize(() => {
        this.isNewUserMethodModal = false;
        this.isUserModalOpen = false;
        this.clearObject(this.newCourseUser);
        this.selectedUserRole = null;
        this.selectedUsers = [];
        this.loadingAction = false;
      }) )
      .subscribe(
        newCourseUser => {
          this.courseUsers.push(newCourseUser);
          this.reduceList('course-users');

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New user created");
          successBox.show().delay(3000).fadeOut();
        });
  }

  addUsersToCourse(): void {
    this.loadingAction = true;

    this.api.addUsersToCourse(this.course.id, this.selectedUsers, this.selectedUserRole)
      .pipe( finalize(() => {
        this.isNewUserMethodModal = false;
        this.isSelectUserModalOpen = false;
        this.selectedUserRole = null;
        this.selectedUsers = [];
        this.loadingAction = false;
      }) )
      .subscribe(newCourseUsers => {
        this.courseUsers = this.courseUsers.concat(newCourseUsers);
        this.reduceList('course-users');

        const successBox = $('#action_completed');
        successBox.empty();
        successBox.append(newCourseUsers.length +  " User" + (newCourseUsers.length != 1 ? "s" : "") + " added");
        successBox.show().delay(3000).fadeOut();
      });
  }

  async editUser(): Promise<void> {
    this.loadingAction = true;
    this.newCourseUser['id'] = this.userToEdit.id;

    if (this.photoToAdd)
      await ResourceManager.getBase64(this.photoToAdd).then(data => this.newCourseUser.image = data);

    this.newCourseUser.roles = this.selectedUserRoles;

    this.api.editCourseUser(this.course.id, this.newCourseUser)
      .pipe( finalize(() => {
        this.isNewUserMethodModal = false;
        this.isUserModalOpen = false;
        this.clearObject(this.newCourseUser);
        this.selectedUserRole = null;
        this.selectedUsers = [];
        this.loadingAction = false;
      }) )
      .subscribe(
        async courseUserEdited => {
          const index = this.courseUsers.findIndex(courseUser => courseUser.id === courseUserEdited.id);
          this.courseUsers.removeAtIndex(index);

          this.courseUsers.push(courseUserEdited);
          this.reduceList('course-users');

          const loggedUser = await this.api.getLoggedUser().toPromise();
          if (loggedUser.id === courseUserEdited.id) {
            if (this.photoToAdd)
              this.updateManager.triggerUpdate(UpdateType.AVATAR); // Trigger change on navbar

            if (!this.userToEdit.roles.isEqual(courseUserEdited.roles))
              this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES); // Trigger change on pages
          }

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: '" + courseUserEdited.name + "' edited");
          successBox.show().delay(3000).fadeOut();
        });
  }

  deleteUser(user: User): void {
    this.loadingAction = true;
    this.api.deleteCourseUser(this.course.id, user.id)
      .pipe( finalize(() => {
        this.isDeleteVerificationModalOpen = false;
        this.loadingAction = false
      }) )
      .subscribe(
        () => {
          const index = this.courseUsers.findIndex(el => el.id === user.id);
          this.courseUsers.removeAtIndex(index);
          this.reduceList('course-users');

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User '" + user.name  + ' - ' + user.studentNumber + "' removed from this course");
          successBox.show().delay(3000).fadeOut();
        });
  }

  toggleActive(userID: number) {
    this.loadingAction = true;

    const courseUser = this.courseUsers.find(user => user.id === userID);
    courseUser.isActiveInCourse = !courseUser.isActiveInCourse;

    this.api.setCourseUserActive(this.course.id, courseUser.id, courseUser.isActiveInCourse)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {});
  }

  importUsers(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedUsers = reader.result;
      this.api.importCourseUsers(this.course.id, {file: importedUsers, replace})
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loadingAction = false;
        }) )
        .subscribe(
          nrUsers => {
            this.getCourseUsers(this.course.id);
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nrUsers + " User" + (nrUsers !== 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          })
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportUsers(): void {
    this.saving = true;

    this.api.exportCourseUsers(this.course.id)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(
        contents => DownloadManager.downloadAsCSV('Users - ' + this.course.name + ' ' + this.course.year, contents)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Roles ------------------- ***/
  /*** --------------------------------------------- ***/

  addRole(roleName: string): void {
    if (!this.selectedUserRoles) this.selectedUserRoles = [];
    this.traverseRoles(this.course.roleHierarchy, roleName, 'add');
  }

  removeRole(roleName: string): void {
    this.traverseRoles(this.course.roleHierarchy, roleName, 'remove');
  }

  traverseRoles(rolesHierarchy: Role[], roleName: string, action: 'add' | 'remove'): boolean {
    for (const role of rolesHierarchy) {

      // Reached role
      if (role.name === roleName) {
        takeAction(action, this.selectedUserRoles, roleName);
        if (action === 'remove' && role.children?.length > 0) {
          for (const child of role.children) {
            if (this.selectedUserRoles.includes(child.name))
              this.traverseRoles(role.children, child.name, action);
          }
        }
        return true;
      }

      // Traverse children
      if (role.children?.length > 0) {
        const tookAction = this.traverseRoles(role.children, roleName, action);
        if (tookAction) { // Take action on parent role as well
          if (action === 'add') takeAction(action, this.selectedUserRoles, role.name);
          return true;
        }
      }
    }
    return false;

    function takeAction(action: 'add' | 'remove', selectedRoles: string[], role: string): void {
      if (action === 'add') {
        if (!selectedRoles.find(el => el === role))
          selectedRoles.push(role);

      } else if (action === 'remove') {
        const index = selectedRoles.findIndex(el => el === role);
        selectedRoles.splice(index, 1);
      }
    }
  }

  filterRoles(): string[] {
    if (!this.selectedUserRoles) return this.rolesNames;
    return this.rolesNames.filter(roleName => !this.selectedUserRoles.includes(roleName));
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    return isValid(this.newCourseUser.name) && isValid(this.newCourseUser.studentNumber) && isValid(this.newCourseUser.email) &&
      isValid(this.newCourseUser.major) && isValid(this.newCourseUser.auth) && isValid(this.newCourseUser.username) &&
      this.selectedUserRoles?.length > 0;
  }

  initEditUser(user: CourseUser): void {
    this.newCourseUser = {
      name: user.name,
      nickname: user.nickname,
      studentNumber: user.studentNumber,
      email: user.email,
      major: user.major,
      auth: user.authMethod,
      username: user.username,
      image: null
    };
    this.userToEdit = user;

    this.originalPhoto = user.photoUrl ?? environment.defaultProfilePicture;
    this.photo.set(user.photoUrl ?? environment.defaultProfilePicture);

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

  async getUsersNotInCourse(): Promise<void> {
    this.nonCourseUsers = await this.api.getUsersNotInCourse(this.course.id, true).toPromise();
    this.reduceList('non-course-users');
  }

  addUser(user: User): void {
    if (!this.selectedUsers) this.selectedUsers = [];

    if (!this.selectedUsers.find(el => el.id === user.id)) {
      this.selectedUsers.push(user);
      const index = this.nonCourseUsers.findIndex(el => el.id === user.id);
      this.nonCourseUsers.removeAtIndex(index);
      this.reduceList('non-course-users');
      this.selectUserQuery = null;
    }
  }

  removeUser(userID: number): void {
    const index = this.selectedUsers.findIndex(el => el.id === userID);
    this.nonCourseUsers.push(this.selectedUsers[index]);
    this.selectedUsers.splice(index, 1);
    this.reduceList('non-course-users');
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

}

export interface CourseUserData {
  id?: number,
  name: string,
  nickname: string,
  studentNumber: number,
  major: string,
  email: string,
  auth: AuthType,
  username: string,
  image: string | ArrayBuffer,
  roles?: string[]
}
