import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {Role} from "../../../../../_domain/roles/role";
import {ErrorService} from "../../../../../_services/error.service";
import {Page} from "../../../../../_domain/pages & templates/page";
import {exists} from "../../../../../_utils/misc/misc";

@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.scss']
})
export class RolesComponent implements OnInit {

  loading: boolean;

  courseID: number;

  defaultRoles: string[] = ['Teacher', 'Student', 'Watcher'];
  roles: Role[];
  rolesHierarchy: Role[];
  pages: Page[];

  selectedPage: {[roleName: string]: string} = {};

  isNewRoleModalOpen: boolean;
  newRole: {name: string, parent: Role} = {name: null, parent: null};
  saving: boolean;

  hasChanges: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getRoles(this.courseID);
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getRoles(courseId: number): void {
    this.api.getRoles(courseId)
      .subscribe(res => {
        this.roles = res.roles;
        this.roles.forEach(role => this.selectedPage[role.name] = role.landingPage);
        this.rolesHierarchy = res.rolesHierarchy;
      },
        error => ErrorService.set(error),
        () => {
        this.loading = false;
        setTimeout(() => {
          const dd = $('#roles-config');
          // @ts-ignore
          dd.nestable({
            expandBtnHTML: '',
            collapseBtnHTML: ''
          });

          dd.on('change', () => this.hasChanges = true);
        }, 0);
      });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  addRole(): void {
    const role = new Role(null, this.newRole.name, '', null);

    if (!this.newRole.parent) {
      this.rolesHierarchy.push(role);

    } else {
      if (!this.newRole.parent.children) this.newRole.parent.children = [];
      this.newRole.parent.children.push(role);
    }
    this.roles.push(role);

    this.hasChanges = true;
    this.isNewRoleModalOpen = false;
    this.clearObject(this.newRole);
  }

  removeRole(role: Role): void {
    if (this.defaultRoles.includes(role.name)) return;

    // Remove children of role
    if (role.children)
      role.children.forEach(child => this.removeRole(child));

    const parent = findParent(this.rolesHierarchy, role, null);

    if (parent) {
      const index = parent.children.findIndex(el => el.name === role.name);
      parent.children.splice(index, 1);
      if (parent.children.length === 0) parent.children = null;

    } else {
      const index = this.rolesHierarchy.findIndex(el => el.name === role.name);
      this.rolesHierarchy.splice(index, 1);
    }

    const index = this.roles.findIndex(el => el.name === role.name);
    this.roles.splice(index, 1);

    this.hasChanges = true;

    function findParent(roles: Role[], roleToFind: Role, parent: Role): Role {
      for (const r of roles) {
        if (r.name === roleToFind.name)
          return parent;
        else if (r.children) {
          const parent = findParent(r.children, roleToFind, r)
          if (parent) return parent;
        }
      }
      return null;
    }
  }

  saveRoles(): void {
    this.loading = true;
    // @ts-ignore
    this.api.saveRoles(this.courseID, this.roles, $('#roles-config').nestable('serialize'))
      .subscribe(
        res => this.getRoles(this.courseID),
        error => ErrorService.set(error),
        () => {
          this.loading = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("Role hierarchy changed!");
          successBox.show().delay(3000).fadeOut();
        })
  }

  saveLandingPage(role: Role): void {
    role.landingPage = this.selectedPage[role.name];
    this.hasChanges = true;
  }

  undo(): void {
    // TODO: update from GameCourse v1
    ErrorService.set('Error: This action still needs to be updated to the current version. (roles.component.ts::undo())');
  }

  redo(): void {
    // TODO: update from GameCourse v1
    ErrorService.set('Error: This action still needs to be updated to the current version. (roles.component.ts::redo())');
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    const roleExists = this.roles.find(role => role.name === this.newRole.name);

    // Validate inputs
    return !roleExists && isValid(this.newRole.name);
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

}
