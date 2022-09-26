import {Component, OnInit, ViewChild} from '@angular/core';
import {NgForm} from "@angular/forms";
import {Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../../_services/update.service";
import {ModalService} from "../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../_services/alert.service";
import {ThemingService} from "../../../../_services/theming/theming.service";
import {ResourceManager} from "../../../../_utils/resources/resource-manager";
import {DownloadManager} from "../../../../_utils/download/download-manager";

import {User} from "../../../../_domain/users/user";
import {AuthType} from "../../../../_domain/auth/auth-type";
import {TableDataType} from "../../../../_components/tables/table-data/table-data.component";
import {Action} from 'src/app/_domain/modules/config/Action';
import {clearEmptyValues} from "../../../../_utils/misc/misc";
import {Theme} from "../../../../_services/theming/themes-available";
import {environment} from "../../../../../environments/environment.prod";


@Component({
  selector: 'app-main',
  templateUrl: './users.component.html'
})
export class UsersComponent implements OnInit {

  loading = {
    page: true,
    table: true,
    action: false
  }

  user: User;
  users: User[];

  mode: 'add' | 'edit';
  userToManage: UserManageData = this.initUserToManage();
  userToDelete: User;
  @ViewChild('f', { static: false }) f: NgForm;

  authMethods: {value: any, text: string}[] = this.initAuthMethods();

  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private themeService: ThemingService,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) { }

  async ngOnInit(): Promise<void> {
    await this.getLoggedUser();
    await this.getUsers();
    this.loading.page = false;

    this.buildTable();
  }

  get Action(): typeof Action {
    return Action;
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getUsers(): Promise<void> {
    this.users = await this.api.getUsers().toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Name (sorting)', align: 'left'},
    {label: 'User', align: 'left'},
    {label: 'Student Nr', align: 'middle'},
    {label: 'Email', align: 'left'},
    {label: '# Courses', align: 'middle'},
    {label: 'Last login (timestamp sorting)', align: 'middle'},
    {label: 'Last login', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Admin', align: 'middle'},
    {label: 'Actions'}
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    order: [[ 0, 'asc' ]], // default order
    columnDefs: [
      { orderData: 0,   targets: 1 },
      { orderData: 5,   targets: 6 },
      { orderable: false, targets: [7, 8, 9] }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.users.forEach(user => {
      table.push([
        {type: TableDataType.TEXT, content: {text: user.name}},
        {type: TableDataType.AVATAR, content: {avatarSrc: user.photoUrl, avatarTitle: user.name, avatarSubtitle: user.major}},
        {type: TableDataType.NUMBER, content: {value: user.studentNumber, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: user.email}},
        {type: TableDataType.NUMBER, content: {value: user.nrCourses}},
        {type: TableDataType.NUMBER, content: {value: user.lastLogin?.unix()}},
        {type: TableDataType.TEXT, content: {text: user.lastLogin?.fromNow() ?? 'Never'}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: user.isActive, toggleDisabled: user.id === this.user.id}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isAdmin', toggleValue: user.isAdmin, toggleColor: 'secondary', toggleDisabled: user.id === this.user.id}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.VIEW, Action.EDIT, Action.DELETE, Action.EXPORT]}},
      ]);
    });

    this.data = table;
    this.loading.table = false;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void {
    const userToActOn = this.users[row];

    if (action === 'value changed') {
      if (col === 7) this.toggleActive(userToActOn);
      else if (col === 8) this.toggleAdmin(userToActOn);

    } else if (action === Action.VIEW) {
      const redirectLink = '/profile/' + userToActOn.id;
      this.router.navigate([redirectLink]);

    } else if (action === Action.EDIT) {
      this.mode = 'edit';
      this.userToManage = this.initUserToManage(userToActOn);
      ModalService.openModal('manage');

    } else if (action === Action.DELETE) {
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
      this.exportUsers(this.users);

    } else if (action === 'Add user') {
      this.mode = 'add';
      this.userToManage = this.initUserToManage();
      ModalService.openModal('manage');
    }
  }

  async createUser(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      if (this.userToManage.photoToAdd)
        await ResourceManager.getBase64(this.userToManage.photoToAdd).then(data => this.userToManage.photoBase64 = data);

      const newUser = await this.api.createUser(clearEmptyValues(this.userToManage)).toPromise();
      this.users.push(newUser);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'New GameCourse user added: ' + newUser.name);

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editUser(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      if (this.userToManage.photoToAdd)
        await ResourceManager.getBase64(this.userToManage.photoToAdd).then(data => this.userToManage.photoBase64 = data);

      const userEdited = await this.api.editUser(clearEmptyValues(this.userToManage)).toPromise();
      const index = this.users.findIndex(user => user.id === userEdited.id);
      this.users.removeAtIndex(index);
      this.users.push(userEdited);

      // Trigger image change
      if (this.userToManage.photoToAdd && this.user.id === userEdited.id)
        this.updateManager.triggerUpdate(UpdateType.AVATAR);

      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'User \'' + userEdited.name + '\' edited');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deleteUser(user: User): Promise<void> {
    this.loading.action = true;

    await this.api.deleteUser(user.id).toPromise();
    const index = this.users.findIndex(el => el.id === user.id);
    this.users.removeAtIndex(index);
    this.buildTable();

    this.loading.action = false;
    ModalService.closeModal('delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'User \'' + user.name + ' - ' + user.studentNumber + '\' deleted');
  }

  async toggleActive(user: User) {
    this.loading.action = true;

    user.isActive = !user.isActive;
    await this.api.setUserActive(user.id, user.isActive).toPromise();

    this.loading.action = false;
  }

  async toggleAdmin(user: User) {
    this.loading.action = true;

    user.isAdmin = !user.isAdmin;
    await this.api.setUserAdmin(user.id, user.isAdmin).toPromise();

    this.loading.action = false;
  }

  async importUsers(): Promise<void> {
    if (this.fImport.valid) {
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrUsersImported = await this.api.importUsers(file, this.importData.replace).toPromise();

      await this.getUsers();
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('import');
      this.resetImport();
      AlertService.showAlert(AlertType.SUCCESS, nrUsersImported + ' user' + (nrUsersImported != 1 ? 's' : '') + ' imported');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async exportUsers(users: User[]): Promise<void> {
    if (users.length === 0)
      AlertService.showAlert(AlertType.WARNING, 'There are no users to export');

    else {
      this.loading.action = true;

      const contents = await this.api.exportUsers(users.map(user => user.id)).toPromise();
      DownloadManager.downloadAsCSV(users.length > 1 ? 'users' : users[0].name, contents);

      this.loading.action = false;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initUserToManage(user?: User): UserManageData {
    const userData: UserManageData = {
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
      photo: new ResourceManager(this.sanitizer)
    };
    if (user) userData.id = user.id;
    if (userData.photoURL) userData.photo.set(userData.photoURL);
    return userData;
  }

  initAuthMethods(): {value: any, text: string}[] {
    return Object.values(AuthType).map(authMethod => {
      return {value: authMethod, text: authMethod.capitalize()}
    });
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

  resetManage() {
    this.mode = null;
    this.initUserToManage();
    this.f.resetForm();
  }

  resetImport() {
    this.importData = {file: null, replace: true};
    this.fImport.resetForm();
  }

}

export interface UserManageData {
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
}
