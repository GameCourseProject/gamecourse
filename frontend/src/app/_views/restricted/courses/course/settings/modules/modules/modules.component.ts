import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";

import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";

import {Course} from "../../../../../../../_domain/courses/course";
import {Module} from "../../../../../../../_domain/modules/module";
import {Reduce} from "../../../../../../../_utils/lists/reduce";
import {ModuleType} from "../../../../../../../_domain/modules/ModuleType";
import {DomSanitizer} from "@angular/platform-browser";
import {DependencyMode} from "../../../../../../../_domain/modules/DependencyMode";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading: boolean = true;

  course: Course;

  modules: Module[];
  modulesTypes: {[key in ModuleType]: string} = {
    GameElement: 'Game Elements',
    DataSource: 'Data Sources',
    Util: 'Tools'
  };

  reduce = new Reduce();
  searchQuery: string;

  isModuleDetailsModalOpen: boolean;
  moduleOpen: Module;
  isEnabled: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getModules(courseID);
      this.loading = false;
    });
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
    this.reduceList();
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.modules, query);
  }

  filterList(modules: Module[], type: ModuleType): Module[] {
    return modules.filter(module => module.type === type);
  }

  filterHardDependencies(modules: Module[]): Module[] {
    return modules.filter(module => module.dependencyMode === DependencyMode.HARD);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleEnabled(module: Module): void {
    this.saving = true;
    const isEnabled = !module.enabled;

    this.api.setModuleState(this.course.id, module.id, isEnabled)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(
        async () => {
          module.enabled = !module.enabled;
          await this.getModules(this.course.id);
          Module.reloadStyles(this.course.id, this.sanitizer);
        },
        error => {},
        () => {
          this.isModuleDetailsModalOpen = false;
          this.moduleOpen = null;
        }
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  objectKeys(obj: object): string[] {
    return Object.keys(obj);
  }

  get ModuleType(): typeof ModuleType {
    return ModuleType;
  }

}
