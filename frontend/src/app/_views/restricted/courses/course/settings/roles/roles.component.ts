import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ModalService} from 'src/app/_services/modal.service';
import {AlertService, AlertType} from "../../../../../../_services/alert.service";

import {Role} from "../../../../../../_domain/roles/role";
import {Course} from "../../../../../../_domain/courses/course";

import * as _ from "lodash";

@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.scss']
})
export class RolesComponent implements OnInit {

  loading = {
    page: true,
    list: true,
    action: false
  }

  showAlert: boolean = false;
  course: Course;
  originalRolesHierarchy: Role[];

  mode: 'add' | 'edit';
  roleToManage: RoleManageData = this.initRoleToManage();
  @ViewChild('f', { static: false }) f: NgForm;

  visiblePages: {value: string, text: string}[];
  defaultRoleNames: string[];
  rolesHierarchySmart: {[roleName: string]: {role: Role, parent: Role, children: Role[]}};

  adaptationRoleNames: string[];
  adaptationTitle: string;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getVisiblePages(courseID);
      await this.getRoles(courseID);
      this.loading.page = false;

      this.buildList();
    });
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

  async getVisiblePages(courseID: number): Promise<void> {
    const pages = await this.api.getCoursePages(courseID, true).toPromise();
    this.visiblePages = pages.map(page => {
      return {value: page.id.toString(), text: page.name};
    })
  }

  async getRoles(courseID: number): Promise<void> {
    // default roles
    this.defaultRoleNames = await this.api.getDefaultRoles(courseID).toPromise();

    // adaptation roles
    this.adaptationRoleNames = await this.api.getAdaptationRoles(courseID, false).toPromise();
    this.adaptationTitle = await this.api.getAdaptationGeneralParent(courseID).toPromise();

    this.originalRolesHierarchy = _.cloneDeep(this.course.roleHierarchy);
    this.initRolesHierarchySmart();
  }


  /*** --------------------------------------------- ***/
  /*** --------------- Nestable List --------------- ***/
  /*** --------------------------------------------- ***/

  buildList(): void {
    setTimeout(() => {
      this.loading.list = true;

      const list = $('#roles-list');
      const options = {
        expandBtnHTML: '',
        collapseBtnHTML: ''
      };

      // @ts-ignore
      list.nestable(options);

      this.loading.list = false;
    }, 0);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  addRole(): void {
    if (this.f.valid) {
      const newRole = new Role(null, this.roleToManage.name, this.roleToManage.landingPage, null);
      this.rolesHierarchySmart[newRole.name] = {role: newRole, parent: this.roleToManage.parent, children: []};

      if (!this.roleToManage.parent) { // no parent
        this.course.roleHierarchy.push(newRole);

      } else { // with parent
        if (!this.roleToManage.parent.children) this.roleToManage.parent.children = [];
        this.roleToManage.parent.children.push(newRole);
      }

      ModalService.closeModal('manage');
      this.resetManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  editRole() {
    if (this.f.valid) {
      this.roleToManage.itself.name = this.roleToManage.name;
      this.roleToManage.itself.landingPage = this.roleToManage.landingPage;

      ModalService.closeModal('manage');
      this.resetManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  removeRole(role: Role): void {
    if (this.isDefaultRole(role.name)) return;
    if (this.isAdaptationRole(role.name)) return;

    const parent = this.rolesHierarchySmart[role.name].parent;
    if (parent) {
      const index = parent.children.findIndex(el => el.name === role.name);
      parent.children.splice(index, 1);
      this.rolesHierarchySmart[parent.name].children.splice(index, 1);
      if (parent.children.length === 0) parent.children = null;

    } else {
      const index = this.course.roleHierarchy.findIndex(el => el.name === role.name);
      this.course.roleHierarchy.removeAtIndex(index);
    }
    delete this.rolesHierarchySmart[role.name];
  }

  discard() {
    this.course.roleHierarchy = _.cloneDeep(this.originalRolesHierarchy);
    this.initRolesHierarchySmart();
    this.showAlert = false;
  }

  async save(): Promise<void> {
    this.loading.action = true;

    const list = $('#roles-list');
    // @ts-ignore
    const hierarchy = list.nestable('serialize');


    let adaptationInStudent = false;
    for (let i = 0; i < hierarchy.length; i++){
      if (hierarchy[i].name === "Student"){

        if (!hierarchy[i].children){ break; }
        for (let j = 0; j < Object.keys(hierarchy[i].children).length; j++){
          if (this.isAdaptationTitle(hierarchy[i].children[j].name)){
            adaptationInStudent = true;
            break;
          }
        }
        break;
      }
    }

    if (this.adaptationTitle && !adaptationInStudent){
      this.showAlert = true;
    } else {
      if (this.showAlert) {this.showAlert = false;}
      await this.api.updateRoles(this.course.id, getRoles(hierarchy, []), hierarchy).toPromise();
      this.originalRolesHierarchy = this.course.roleHierarchy;

      function getRoles(hierarchy: Role[], roles: Role[]) {
        for (const role of hierarchy) {
          role['landingPage'] = role['landingpage'];
          delete role['landingpage'];

          // @ts-ignore
          const copiedRole = _.cloneDeep(role);
          delete role['id'];
          delete role['landingPage'];

          roles.push(copiedRole);
          if (role.children?.length > 0)
            roles = [...new Set(roles.concat(getRoles(role.children, roles)))]
        }
        return roles;
      }

      AlertService.showAlert(AlertType.SUCCESS, 'Roles saved');
    }

    this.loading.action = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initRoleToManage(role?: Role, parent?: Role): RoleManageData {
    const roleData: RoleManageData = {
      name: role?.name ?? null,
      landingPage: role?.landingPage ?? null,
      parent: parent ?? null,
      itself: role ?? null
    };
    if (role) roleData.id = role.id;
    return roleData;
  }

  initRolesHierarchySmart() {
    const that = this;
    this.rolesHierarchySmart = {};
    init(this.course.roleHierarchy, null);

    function init(rolesHierarchy: Role[], parent: Role) {
      for (const role of rolesHierarchy) {
        that.rolesHierarchySmart[role.name] = {role, parent, children: []};
        if (parent) that.rolesHierarchySmart[parent.name].children.push(role);

        // Traverse children
        if (role.children?.length > 0)
          init(role.children, role);
      }
    }
  }

  resetManage() {
    this.mode = null;
    this.initRoleToManage();
    this.f.resetForm();
  }

  isDefaultRole(roleName): boolean {
    return this.defaultRoleNames.includes(roleName);
  }

  isAdaptationRole(roleName): boolean {
    return this.adaptationRoleNames.includes(roleName);
  }

  isAdaptationTitle(roleName): boolean {
    return roleName === this.adaptationTitle;
  }

}

export interface RoleManageData {
  id?: number,
  name: string,
  landingPage: number,
  parent: Role,
  itself: Role
}
