import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {Role} from "../../../../../../_domain/roles/role";
import {Page} from "../../../../../../_domain/pages & templates/page";
import {exists} from "../../../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";
import {Course} from "../../../../../../_domain/courses/course";

@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.scss']
})
export class RolesComponent implements OnInit {

  loading: boolean = true;
  course: Course;
  activePages: Page[];

  defaultRoles: string[];
  roles: Role[];

  isNewRoleModalOpen: boolean;
  newRole: RoleData = {
    name: null,
    parent: null,
    landingPage: null
  };
  saving: boolean;
  hasChanges: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getActivePages(courseID);
      await this.getDefaultRoles(courseID);
      await this.getRoles(courseID);
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getActivePages(courseID: number): Promise<void> {
    this.activePages = []; // FIXME
  }

  async getDefaultRoles(courseID: number): Promise<void> {
    this.defaultRoles = await this.api.getDefaultRoles(courseID).toPromise();
  }

  async getRoles(courseID: number): Promise<void> {
    this.roles = await this.api.getRoles(courseID, false).toPromise() as Role[];
    setTimeout(() => {
      const dd = $('#roles-config');
      // @ts-ignore
      dd.nestable({
        expandBtnHTML: '',
        collapseBtnHTML: ''
      });
      dd.on('change', () => this.hasChanges = true);
    }, 0);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  addRole(): void {
    const role = new Role(null, this.newRole.name, null, null);
    this.roles.push(role)

    if (!this.newRole.parent) {
      this.course.roleHierarchy.push(role);

    } else {
      if (!this.newRole.parent.children) this.newRole.parent.children = [];
      this.newRole.parent.children.push(role);
    }

    this.hasChanges = true;
    this.isNewRoleModalOpen = false;
    this.clearObject(this.newRole);
  }

  removeRole(role: Role): void {
    if (this.defaultRoles.includes(role.name)) return;

    // Remove children of role
    if (role.children)
      role.children.forEach(child => this.removeRole(child));

    const parent = findParent(this.course.roleHierarchy, role, null);

    if (parent) {
      const index = parent.children.findIndex(el => el.name === role.name);
      parent.children.splice(index, 1);
      if (parent.children.length === 0) parent.children = null;

    } else {
      const index = this.course.roleHierarchy.findIndex(el => el.name === role.name);
      this.course.roleHierarchy.removeAtIndex(index);
    }

    const index = this.roles.findIndex(el => el.name === role.name);
    this.roles.removeAtIndex(index);

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
    const hierarchy = $('#roles-config').nestable('serialize');
    this.api.updateRoles(this.course.id, this.roles, hierarchy)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => {
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("Role hierarchy changed!");
          successBox.show().delay(3000).fadeOut();
        })
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty() && !/\s/g.test(text);
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

export interface RoleData {
  name: string,
  parent: Role,
  landingPage: number
}
