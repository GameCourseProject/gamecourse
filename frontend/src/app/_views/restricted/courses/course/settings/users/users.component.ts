import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";
import {NgForm} from "@angular/forms";
import {Subject} from "rxjs";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../../../../_services/update.service";
import {ThemingService} from "../../../../../../_services/theming/theming.service";
import {ModalService} from "../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {ResourceManager} from "../../../../../../_utils/resources/resource-manager";

import {User} from "../../../../../../_domain/users/user";
import {CourseUser} from "../../../../../../_domain/users/course-user";
import {Course} from "../../../../../../_domain/courses/course";
import {Role} from "../../../../../../_domain/roles/role";
import {AuthType} from 'src/app/_domain/auth/auth-type';
import {Action} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {Theme} from "../../../../../../_services/theming/themes-available";
import {environment} from "../../../../../../../environments/environment";
import {DownloadManager} from "../../../../../../_utils/download/download-manager";

@Component({
  selector: 'app-users',
  templateUrl: './users.component.html'
})
export class UsersComponent implements OnInit {

  loading = {
    page: true,
    table: true,
    action: false,
    roles: false,
    nonCourseUsers: false
  }

  course: Course;
  courseUsers: CourseUser[];

  mode: 'add' | 'edit' | 'select' | 'roles';
  userToManage: CourseUserManageData = this.initUserToManage();
  userToDelete: CourseUser;
  @ViewChild('f', { static: false }) f: NgForm;

  authMethods: {value: any, text: string}[] = this.initAuthMethods();

  rolesHierarchySmart: {[roleName: string]: {parent: Role, children: Role[]}};
  roleNames: {value: string, text: string, innerHTML: string, selected?: boolean}[];
  previousSelected: string[];
  setRoles: Subject<{value: string, text: string, innerHTML: string, selected: boolean}[]> = new Subject();

  nonCourseUsers: {value: string, text: string}[];
  selection: {usersToAdd: string[], roleNames: string[]};
  @ViewChild('fSelect', { static: false }) fSelect: NgForm;

  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private route: ActivatedRoute,
    private themeService: ThemingService,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getCourseUsers(courseID);
      this.loading.page = false;

      this.buildTable();
    });
  }

  get Action(): typeof Action {
    return Action;
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

  get ModalService(): typeof ModalService {
    return ModalService;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getCourseUsers(courseID: number): Promise<void> {
    this.courseUsers = await this.api.getCourseUsers(courseID).toPromise();
  }

  async getCourseRolesNames(courseID: number): Promise<void> {
    this.loading.roles = true;

    const that = this;
    this.rolesHierarchySmart = {};

    setTimeout(async () => {
      const rolesHierarchy = await this.api.getRoles(courseID, false, true).toPromise() as Role[];
      const roles = getRolesByHierarchy(rolesHierarchy, [], null, 0);
      this.roleNames = roles.map(role => {
        return {
          value: role.name,
          text: role.name,
          innerHTML: '<span style="padding-left: ' + (15 * role.depth) + 'px;">' + role.name + '</span>'
        };
      });

      this.loading.roles = false;
    });

    function getRolesByHierarchy(rolesHierarchy: Role[], roles: {name: string, depth: number}[], parent: Role, depth: number): {name: string, depth: number}[] {
      for (const role of rolesHierarchy) {
        roles.push({name: role.name, depth});
        that.rolesHierarchySmart[role.name] = {parent, children: []};
        if (parent) that.rolesHierarchySmart[parent.name].children.push(role);

        // Traverse children
        if (role.children?.length > 0) {
          roles = getRolesByHierarchy(role.children, roles, role, depth + 1);
        }
      }
      return roles;
    }
  }

  async getUsersNotInCourse(courseID: number): Promise<void> {
    this.loading.nonCourseUsers = true;

    const nonCourseUsers = await this.api.getUsersNotInCourse(courseID, true).toPromise();
    this.nonCourseUsers = nonCourseUsers.map(user => {
      return {value: 'id-' + user.id, text: user.name};
    });
    this.selection = {usersToAdd: [], roleNames: []};

    this.loading.nonCourseUsers = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Name (sorting)', align: 'left'},
    {label: 'User', align: 'left'},
    {label: 'Roles', align: 'left'},
    {label: 'Student Nr', align: 'middle'},
    {label: 'Major', align: 'middle'},
    {label: 'Last activity (timestamp sorting)', align: 'middle'},
    {label: 'Last activity', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Actions'}
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    order: [[ 0, 'asc' ], [ 3, 'asc' ]], // default order
    columnDefs: [
      { orderData: 0,   targets: 1 },
      { orderData: 5,   targets: 6 },
      { orderable: false, targets: [2, 7, 8] }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.courseUsers.forEach(user => {
      table.push([
        {type: TableDataType.TEXT, content: {text: user.nickname ?? user.name}},
        {type: TableDataType.AVATAR, content: {avatarSrc: user.photoUrl, avatarTitle: user.nickname ?? user.name, avatarSubtitle: user.major}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="flex flex-col gap-2">' +
              user.roles
                .sort((a, b) => a.name.localeCompare(b.name))
                .map(role => '<div class="badge badge-outline badge-primary">' + role.name + '</div>').join('') +
              '</div>'
        }},
        {type: TableDataType.NUMBER, content: {value: user.studentNumber, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: user.major}},
        {type: TableDataType.NUMBER, content: {value: user.lastActivity?.unix()}},
        {type: TableDataType.TEXT, content: {text: user.lastActivity?.fromNow() ?? 'Never'}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: user.isActiveInCourse}},
        {type: TableDataType.ACTIONS, content: {actions: [
          Action.VIEW,
          {action: 'Manage roles', icon: 'tabler-id-badge-2', color: 'primary'},
          Action.EDIT, Action.REMOVE, Action.EXPORT]}},
      ]);
    });

    this.data = table;
    this.loading.table = false;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void {
    const userToActOn = this.courseUsers[row];

    if (action === 'value changed') {
      if (col === 7) this.toggleActive(userToActOn);

    } else if (action === Action.VIEW) {
      const redirectLink = '/profile/' + userToActOn.id;
      this.router.navigate([redirectLink]);

    } else if (action === 'Manage roles') {
      this.mode = 'roles';
      this.userToManage = this.initUserToManage(userToActOn);
      if (!this.roleNames) this.getCourseRolesNames(this.course.id);
      ModalService.openModal('manage-roles');

    } else if (action === Action.EDIT) {
      this.mode = 'edit';
      this.userToManage = this.initUserToManage(userToActOn);
      ModalService.openModal('manage');

    } else if (action === Action.REMOVE) {
      this.userToDelete = userToActOn;
      ModalService.openModal('delete-verification');

    } else if (action === Action.EXPORT) {
      this.exportUsers([userToActOn]);
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  doAction(action: string) {
    if (action === Action.IMPORT) {
      ModalService.openModal('import');

    } else if (action === Action.EXPORT) {
      this.exportUsers(this.courseUsers);

    } else if (action === 'Add user') {
      this.showAddUserOptions();
    }
  }

  async createUser(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      if (this.userToManage.photoToAdd)
        await ResourceManager.getBase64(this.userToManage.photoToAdd).then(data => this.userToManage.photoBase64 = data);

      const newUser = await this.api.createCourseUser(this.course.id, clearEmptyValues(this.userToManage)).toPromise();
      this.courseUsers.push(newUser);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'User \'' + newUser.name + '\' added to course');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async addUsersToCourse(): Promise<void> {
    if (this.fSelect.valid) {
      this.loading.action = true;

      const userIDs = this.selection.usersToAdd.map(id => parseInt(id.substring(3)));
      const newUsers = await this.api.addUsersToCourse(this.course.id, userIDs, this.selection.roleNames).toPromise();
      this.courseUsers = this.courseUsers.concat(newUsers);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('select');
      this.resetSelect();
      AlertService.showAlert(AlertType.SUCCESS, newUsers.length + ' user' + (newUsers.length != 1 ? 's' : '') + ' added to course');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editUser(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      if (this.userToManage.photoToAdd)
        await ResourceManager.getBase64(this.userToManage.photoToAdd).then(data => this.userToManage.photoBase64 = data);

      const userEdited = await this.api.editCourseUser(this.course.id, clearEmptyValues(this.userToManage)).toPromise();
      const index = this.courseUsers.findIndex(user => user.id === userEdited.id);
      this.courseUsers.removeAtIndex(index);
      this.courseUsers.push(userEdited);

      // Trigger changes
      const loggedUser = await this.api.getLoggedUser().toPromise();
      if (loggedUser.id === userEdited.id) {
        if (this.userToManage.photoToAdd)
          this.updateManager.triggerUpdate(UpdateType.AVATAR); // Trigger image change

        if (!this.userToManage.roleNames.isEqual(userEdited.roles.map(role => role.name)))
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES); // Trigger pages change
      }

      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage');
      ModalService.closeModal('manage-roles');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'User \'' + userEdited.name + '\' edited');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deleteUser(user: User): Promise<void> {
    this.loading.action = true;

    await this.api.deleteCourseUser(this.course.id, user.id).toPromise();
    const index = this.courseUsers.findIndex(el => el.id === user.id);
    this.courseUsers.removeAtIndex(index);
    this.buildTable();

    this.loading.action = false;
    ModalService.closeModal('delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'User \'' + user.name + ' - ' + user.studentNumber + '\' was removed from this course');
  }

  async toggleActive(courseUser: CourseUser) {
    this.loading.action = true;

    courseUser.isActiveInCourse = !courseUser.isActiveInCourse;
    await this.api.setCourseUserActive(this.course.id, courseUser.id, courseUser.isActiveInCourse).toPromise();

    this.loading.action = false;
  }

  async importUsers(): Promise<void> {
    if (this.fImport.valid) {
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrUsersImported = await this.api.importCourseUsers(this.course.id, file, this.importData.replace).toPromise();

      await this.getCourseUsers(this.course.id);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('import');
      this.resetImport();
      AlertService.showAlert(AlertType.SUCCESS, nrUsersImported + ' user' + (nrUsersImported != 1 ? 's' : '') + ' imported');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async exportUsers(users: CourseUser[]): Promise<void> {
    if (users.length === 0)
      AlertService.showAlert(AlertType.WARNING, 'There are no users in the course to export');

    else {
      this.loading.action = true;

      const contents = await this.api.exportCourseUsers(this.course.id, users.map(user => user.id)).toPromise();
      DownloadManager.downloadAsCSV((this.course.short ?? this.course.name) + '-' + (users.length === 1 ? users[0].name : 'users'), contents);

      this.loading.action = false;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Roles ------------------- ***/
  /*** --------------------------------------------- ***/

  updateRoles(selectedRoleNames: string[]): void {
    if (selectedRoleNames.length > this.previousSelected.length) { // adding role
      const roleToAdd = selectedRoleNames.filter(roleName => !this.previousSelected.includes(roleName))[0];

      // Get role parents to select as well
      selectedRoleNames = this.selectRoles(roleToAdd, selectedRoleNames, 'add');

    } else { // removing role
      const roleToRemove = this.previousSelected.filter(roleName => !selectedRoleNames.includes(roleName))[0];

      // Remove role's children
      selectedRoleNames = this.selectRoles(roleToRemove, selectedRoleNames, 'remove');
    }

    // Select them
    this.roleNames.map(option => {
      option['selected'] = selectedRoleNames.includes(option.value);
      return option;
    });
    this.setRoles.next(this.roleNames as {value: any, text: string, innerHTML: string, selected: boolean}[]);
    this.previousSelected = selectedRoleNames;
  }

  selectRoles(roleName: string, selectedRoleNames: string[], action: 'add' | 'remove'): string[] {
    if (!roleName) return [];

    if (action === 'add') {
      if (!selectedRoleNames.includes(roleName)) selectedRoleNames.push(roleName);

      const parent = this.rolesHierarchySmart[roleName].parent;
      if (parent) selectedRoleNames = this.selectRoles(parent.name, selectedRoleNames, action);

    } else if (action === 'remove') {
      if (selectedRoleNames.includes(roleName))
        selectedRoleNames.splice(selectedRoleNames.indexOf(roleName), 1);

      const children = this.rolesHierarchySmart[roleName].children;
      for (const child of children) {
        selectedRoleNames = this.selectRoles(child.name, selectedRoleNames, action);
      }
    }

    return selectedRoleNames;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initUserToManage(user?: CourseUser): CourseUserManageData {
    const userData: CourseUserManageData = {
      name: user?.name ?? null,
      email: user?.email ?? null,
      major: user?.major ?? null,
      nickname: user?.nickname ?? null,
      studentNr: user?.studentNumber ?? null,
      username: user?.username ?? null,
      authService: user?.authMethod ?? null,
      photoURL: user?.photoUrl,
      photoToAdd: null,
      photoBase64: null,
      photo: new ResourceManager(this.sanitizer),
      roleNames: user?.roles?.map(role => role.name) ?? []
    };
    if (user) userData.id = user.id;
    if (userData.photoURL) userData.photo.set(userData.photoURL);
    this.previousSelected = user ? userData.roleNames : [];
    return userData;
  }

  initAuthMethods(): {value: any, text: string}[] {
    return Object.values(AuthType).map(authMethod => {
      return {value: authMethod, text: authMethod.capitalize()}
    });
  }

  initSelect() {
    this.previousSelected = [];
    return {usersToAdd: [], roleNames: []};
  }

  get DefaultProfileImg(): string {
    const theme = this.themeService.getTheme();
    return theme === Theme.DARK ? environment.userPicture.dark : environment.userPicture.light;
  }

  onFileSelected(files: FileList, type: 'image' | 'file'): void {
    if (type === 'image') {
      this.userToManage.photoToAdd = files.item(0);
      this.userToManage.photo.set(this.userToManage.photoToAdd);

    } else {
      this.importData.file = files.item(0);
    }
  }

  showAddUserOptions() {
    const options = document.getElementById('add-user-options');
    options.classList.add('dropdown-open');
  }

  hideAddUserOptions() {
    const options = document.getElementById('add-user-options');
    options.classList.remove('dropdown-open');
  }

  resetManage() {
    this.mode = null;
    this.initUserToManage();
    this.f.resetForm();
  }

  resetSelect() {
    this.mode = null;
    this.initSelect();
    this.fSelect.resetForm();
  }

  resetImport() {
    this.importData = {file: null, replace: true};
    this.fImport.resetForm();
  }

}

export interface CourseUserManageData {
  id?: number,
  name: string,
  email: string,
  major: string,
  nickname: string,
  studentNr: number,
  username: string,
  authService: AuthType,
  photoURL: string;                       // Original photo URL
  photoToAdd: File;                       // Any photo that comes through the input
  photoBase64: string | ArrayBuffer;      // Base64 of uploaded photo
  photo: ResourceManager;                 // Photo to be displayed
  roleNames?: string[]
}
