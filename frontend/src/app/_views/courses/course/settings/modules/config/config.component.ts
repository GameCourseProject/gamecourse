import {Component, OnInit, ViewChild} from '@angular/core';
import {Module} from "../../../../../../_domain/modules/module";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ErrorService} from "../../../../../../_services/error.service";
import {finalize} from "rxjs/operators";
import {InputType} from "../../../../../../_domain/inputs/input-type";
import {NgForm} from "@angular/forms";

@Component({
  selector: 'app-config',
  templateUrl: './config.component.html',
  styleUrls: ['./config.component.scss']
})
export class ConfigComponent implements OnInit {

  loading: boolean;

  courseID: number;
  module: Module;
  courseFolder: string;

  generalInputs: {id: string, name: string, type: InputType, options: string, current_val: any}[];
  listingItems: {listName: string, itemName: string, header: string[], displayAttributes: string[], items: any[],
                 allAttributes: {id: string, name: string, type: InputType, options: string}[]}[];
  personalizedConfig; // TODO: put type
  tiers; // TODO: put type

  importedFile: File;

  hasUnsavedChanges: boolean;

  @ViewChild('f', { static: false }) f: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(childParams => {
        this.getModuleConfigInfo(childParams.id);
      });
    });
  }

  get InputType(): typeof InputType {
    return InputType;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getModuleConfigInfo(moduleId: string): void {
    this.loading = true;
    this.api.getModuleConfigInfo(this.courseID, moduleId)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        info => {
          console.log(info)
          this.module = info.module;
          this.courseFolder = info.courseFolder;
          this.generalInputs = info.generalInputs;
          this.listingItems = info.listingItems;
          this.personalizedConfig = info.personalizedConfig;
          this.tiers = info.tiers;
        },
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  saveGeneralInputs() {
    this.loading = true;

    // Parse inputs
    const inputsObj = {};
    for (const input of this.generalInputs) {
      inputsObj[input.id] = input.current_val;
    }

    this.api.saveModuleConfigInfo(this.courseID, this.module.id, inputsObj)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.hasUnsavedChanges = false,
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  capitalize(str: string): string {
    return str.capitalize();
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

}
