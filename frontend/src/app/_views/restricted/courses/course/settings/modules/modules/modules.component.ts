import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import { ModalService } from 'src/app/_services/modal.service';

import {Course} from "../../../../../../../_domain/courses/course";
import {Module} from "../../../../../../../_domain/modules/module";
import {ModuleType} from "../../../../../../../_domain/modules/ModuleType";
import {DependencyMode} from "../../../../../../../_domain/modules/DependencyMode";

import * as _ from "lodash";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html'
})
export class ModulesComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  course: Course;

  modules: Module[];
  filteredModules: Module[];

  modulesTypes: {[key in ModuleType]: string} = {
    GameElement: 'Game Elements',
    DataSource: 'Data Sources',
    Util: 'Tools'
  };

  moduleToManage: Module;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getModules(courseID);
      this.loading.page = false;
    });
  }

  get ModuleType(): typeof ModuleType {
    return ModuleType;
  }

  get ModuleService(): typeof ModalService {
    return ModalService;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getModules(courseID: number): Promise<void> {
    this.modules = (await this.api.getCourseModules(courseID).toPromise())
      .sort((a, b) => a.name.localeCompare(b.name));
    this.filteredModules = this.modules;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async toggleEnabled(module: Module): Promise<void> {
    this.loading.action = true;
    const isEnabled = !module.enabled;

    await this.api.setModuleState(this.course.id, module.id, isEnabled).toPromise();
    module.enabled = isEnabled;
    await this.getModules(this.course.id);
    Module.reloadStyles(this.course.id, this.sanitizer);

    this.loading.action = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initModuleToManage(module: Module): Module {
    const moduleToManage = _.cloneDeep(module) as Module;
    moduleToManage.icon.svg = moduleToManage.icon.svg.replace('<svg', '<svg id="' + module.id + '-modal-icon"');
    return moduleToManage;
  }

  initIcon() {
    setTimeout(() => {
      const svg = document.getElementById(this.moduleToManage.id + '-modal-icon');
      svg.style.width = '2.5rem';
      svg.style.height = '2.5rem';
    });
  }

  objectKeys(obj: object): string[] {
    return Object.keys(obj);
  }

  filterModules(type: ModuleType): Module[] {
    return this.filteredModules.filter(module => module.type === type);
  }

  filterHardDependencies(modules: Module[]): Module[] {
    return modules.filter(module => module.dependencyMode === DependencyMode.HARD);
  }

  configure() {
    this.router.navigate(['modules/' + this.moduleToManage.id + '/config'], {relativeTo: this.route.parent});
  }

}
