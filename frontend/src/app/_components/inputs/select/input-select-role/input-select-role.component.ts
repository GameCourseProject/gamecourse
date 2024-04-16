import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";
import { Role } from 'src/app/_domain/roles/role';
import { ApiHttpService } from 'src/app/_services/api/api-http.service';

@Component({
  selector: 'app-input-select-role',
  templateUrl: './input-select-role.component.html'
})
export class InputSelectRoleComponent implements OnInit {

  @Input() courseId: number;

  loading: boolean = true;
  roleNames: { value: string, text: string, html: string, selected?: boolean }[];
  rolesHierarchySmart: {[roleName: string]: {parent: Role, children: Role[]}};

  // Essentials
  @Input() id: string;                                                // Unique ID
  @Input() form: NgForm;                                              // Form it's part of
  @Input() value: any;                                                // Where to store the value
  @Input() placeholder: string = 'Select role(s)';                    // Message to show by default

  @Input() multiple?: boolean;                                        // Whether to allow multiple selects
  @Input() limit?: number;                                            // Multiple selection limit
  @Input() search?: boolean = true;                                   // Allow to search options
  @Input() closeOnSelect?: boolean = true;                            // Whether to close upon selecting a value
  @Input() hideSelectedOption?: boolean = false;                       // Hide selected options

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                   // Size  FIXME: not working
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |     // Color FIXME: not working
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                        // Classes to add
  @Input() disabled?: boolean;                                        // Make it disabled

  @Input() topLabel?: string;                                         // Top label text
  @Input() leftLabel?: string;                                        // Text on prepended label
  @Input() rightLabel?: string;                                       // Text on appended label

  @Input() btnText?: string;                                          // Text on appended button
  @Input() btnIcon?: string;                                          // Icon on appended button

  @Input() helperText?: string;                                       // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';      // Helper position

  // Validity
  @Input() required?: boolean;                                        // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error

  @Output() valueChange = new EventEmitter<any | any[]>();
  @Output() btnClicked = new EventEmitter<any | any[]>();

    constructor(
        private api: ApiHttpService,
    ) { }

    async ngOnInit() {
      this.loading = true;

      const that = this;
      this.rolesHierarchySmart = {};

      const rolesHierarchy = await this.api.getRoles(this.courseId, false, true).toPromise() as Role[];
      const roles = getRolesByHierarchy(rolesHierarchy, [], null, 0);

      this.roleNames = roles.map(role => {
        return {
            value: role.name,
            text: role.name,
            html: '<span style="padding-left: ' + (15 * role.depth) + 'px;">' + role.name + '</span>'
        };
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

      this.loading = false;
    }
}
