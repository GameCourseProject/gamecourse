import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../_services/error.service";
import {UpdateService, UpdateType} from "../../../../_services/update.service";

import {User} from "../../../../_domain/users/user";
import {Course} from "../../../../_domain/courses/course";
import {Role} from "../../../../_domain/roles/role";
import {AuthType} from 'src/app/_domain/auth/auth-type';
import {UserData} from "../../../my-info/my-info/my-info.component";

import {ImageManager} from "../../../../_utils/images/image-manager";
import {Order, Sort} from "../../../../_utils/display/order";
import {DownloadManager} from "../../../../_utils/download/download-manager";
import {Reduce} from "../../../../_utils/display/reduce";

import _ from 'lodash';
import {exists} from "../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";

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
  rolesHierarchy: Role[];

  allUsers: User[];
  allNonUsers: User[];

  reduce = new Reduce();
  order = new Order();

  reduceNonUsers = new Reduce();

  filters: string[];
  orderBy = ['Name', 'Nickname', 'Student Number', 'Last Login'];

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
  selectedUsers: User[] = [];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getLoggedUser();
    this.route.params.subscribe(params => {
      this.getCourse(parseInt(params.id));
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
    this.api.getRoles(courseId)
      .subscribe(
        data => {
          this.roles = data.roles;
          this.rolesHierarchy = data.rolesHierarchy
          this.filters = this.roles.map(role => role.name);
          this.getCourseUsers(courseId);
        },
        error => ErrorService.set(error));
  }

  getCourseUsers(courseId: number): void {
    this.api.getCourseUsers(courseId)
      .subscribe(users => {
        this.allUsers = users;

        this.order.active = { orderBy: this.orderBy[0], sort: Sort.ASCENDING };
        this.reduceList(undefined, _.cloneDeep(this.filters));

        this.getNonCourseUsers(courseId);
      },
        error => ErrorService.set(error))
  }

  getNonCourseUsers(courseId: number): void {
    this.api.getNotCourseUsers(courseId)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(users => {
        this.allNonUsers = users;
        this.reduceListNonUsers();
      }, error => ErrorService.set(error));
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string, filters?: string[]): void {
    this.reduce.searchAndFilter(this.allUsers, query, filters);
    this.orderList();
  }

  reduceListNonUsers(query?: string): void {
    this.reduceNonUsers.search(this.allNonUsers, query);
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
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleActive(userID: number) {
    this.loadingAction = true;

    const user = this.allUsers.find(user => user.id === userID);
    user.isActive = !user.isActive;

    this.api.setCourseUserActive(this.course.id, user.id, user.isActive)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(
        res => {},
        error => ErrorService.set(error)
      );
  }

  async createUser(): Promise<void> {
    this.loadingAction = true;

    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.newUser.roles = this.selectedUserRoles || [];

    this.api.createCourseUser(this.course.id, this.newUser)
      .pipe( finalize(() => {
        this.isNewUserMethodModal = false;
        this.isUserModalOpen = false;
        this.clearObject(this.newUser);
        this.loadingAction = false;
      }) )
      .subscribe(
        () => {
          this.getCourseUsers(this.course.id);
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New user created");
          successBox.show().delay(3000).fadeOut();
        }, error => ErrorService.set(error));
  }

  async editUser(): Promise<void> {
    this.loadingAction = true;
    this.newUser['id'] = this.userToEdit.id;

    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.newUser.roles = this.selectedUserRoles || [];

    this.api.editCourseUser(this.course.id, this.newUser)
      .pipe( finalize(() => {
        this.isNewUserMethodModal = false;
        this.isUserModalOpen = false;
        this.clearObject(this.newUser);
        this.loadingAction = false;
      }) )
      .subscribe(
        () => {
          this.getCourseUsers(this.course.id);

          if (this.user.id === this.newUser.id) {
            if (this.newUser.image)
              this.updateManager.triggerUpdate(UpdateType.AVATAR); // Trigger change on navbar

            if (!this.userToEdit.roles.map(role => role.name).isEqual(this.newUser.roles))
              this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES); // Trigger change on pages
          }

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: " + this.userToEdit.name + " edited");
          successBox.show().delay(3000).fadeOut();
        }, error => ErrorService.set(error));
  }

  submitUsers(): void {
    this.api.addUsersToCourse(this.course.id, this.selectedUsers, this.selectedUserRole)
      .pipe( finalize(() => {
        this.isNewUserMethodModal = false;
        this.isSelectUserModalOpen = false;
        this.selectedUserRole = null;
        this.selectedUsers = null;
        this.loadingAction = false;
      }) )
      .subscribe(() => {
        this.getCourseUsers(this.course.id);
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New User(s) added");
          successBox.show().delay(3000).fadeOut();
        }, error => ErrorService.set(error));
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
          const index = this.allUsers.findIndex(el => el.id === user.id);
          this.allUsers.splice(index, 1);
          this.reduceList();
          this.getNonCourseUsers(this.course.id);

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: " + user.name  + ' - ' + user.studentNumber + " removed from this course");
          successBox.show().delay(3000).fadeOut();
        }, error => ErrorService.set(error));
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
          nUsers => {
            this.getCourseUsers(this.course.id);
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nUsers + " Users" + (nUsers > 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error),
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportUsers(): void {
    this.saving = true;

    this.api.exportCourseUsers(this.course.id)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(
        contents => DownloadManager.downloadAsCSV('Users - ' + this.course.name + ' ' + this.course.year, contents),
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
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
      const index = this.allNonUsers.findIndex(el => el.id === user.id);
      this.allNonUsers.splice(index, 1);
      this.reduceListNonUsers();
    }
  }

  removeUser(userID: number): void {
    const index = this.selectedUsers.findIndex(el => el.id === userID);
    this.allNonUsers.push(this.selectedUsers[index]);
    this.selectedUsers.splice(index, 1);
    this.reduceListNonUsers();
  }

  addRole(role: string): void {
    if (!this.selectedUserRoles) this.selectedUserRoles = [];
    this.traverseRoles(this.rolesHierarchy, role, 'add');
  }

  removeRole(role: string): void {
    this.traverseRoles(this.rolesHierarchy, role, 'remove');
  }

  traverseRoles(roles: Role[], roleName: string, action: 'add' | 'remove'): boolean {
    for (const role of roles) {

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

  filterRoles(): Role[] {
    if (!this.selectedUserRoles) return this.roles;
    return this.roles.filter(el => !this.selectedUserRoles.includes(el.name));
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

}
