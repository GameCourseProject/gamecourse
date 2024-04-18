import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {ViewEditorService} from "../../../../../../../../_services/view-editor.service";
import {ModalService} from "../../../../../../../../_services/modal.service";
import {Aspect} from "../../../../../../../../_domain/views/aspects/aspect";
import {Course} from "../../../../../../../../_domain/courses/course";
import * as _ from "lodash";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";

@Component({
  selector: 'app-aspects-manager',
  templateUrl: './aspects-manager.component.html'
})
export class AspectsManagerComponent implements OnInit{

  @Input() course: Course;

  @Input() aspects: Aspect[];                     // Original aspects
  aspectsToEdit: Aspect[];                        // Aspects that show up in modal
  aspectToSelect: Aspect = null;                  // Aspect that user clicked, so is highlighted
  currentAspect: Aspect = null;                   // Aspect that is being shown in the editor

  // New Aspect to Add
  viewerRole: string = null;
  userRole: string = null;
  aspectToCopy: string = null;

  modal: boolean = false;

  @ViewChild('f', { static: false }) f: NgForm;
  @Output() save = new EventEmitter<{ aspects: Aspect[] }>();
  @Output() switch = new EventEmitter<{ aspect: Aspect }>();
  @Output() discard = new EventEmitter();

  constructor(
    public service: ViewEditorService
  ) { }

  ngOnInit(): void {
    this.aspectsToEdit = _.cloneDeep(this.aspects);
    this.currentAspect = this.service.selectedAspect;
    this.service.aspectsToDelete = [];
    this.service.aspectsToChange = [];
    this.service.aspectsToAdd = [];
    this.modal = true;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  selectAspect(aspect: Aspect) {
    this.aspectToSelect = aspect;
  }

  setCurrent(aspect: Aspect) {
    this.currentAspect = aspect;
  }

  openCreateNewAspectModal() {
    this.modal = true;
    ModalService.openModal("create-new-aspect");
  }

  createNewAspect() {
    if (this.f.valid) {
      const viewerRole = this.viewerRole != "" ? this.viewerRole : null;
      const userRole = this.userRole != "" ? this.userRole : null;
      const newAspect = new Aspect(viewerRole, userRole);

      let [viewerToCopy, userToCopy] = this.aspectToCopy.split(" | ");
      if (viewerToCopy === 'none') viewerToCopy = null;
      if (userToCopy === 'none') userToCopy = null;
      const aspectToCopy = new Aspect(viewerToCopy, userToCopy);

      if (this.aspectsToEdit.findIndex(e => _.isEqual(e, newAspect)) == -1) {
        this.aspectsToEdit.push(newAspect);
        this.service.aspectsToAdd.push({newAspect: newAspect, aspectToCopy: aspectToCopy});

        ModalService.closeModal("create-new-aspect");

        // reset form
        this.aspectToCopy = null;
        this.viewerRole = null;
        this.userRole = null;
        this.f.resetForm();
        this.modal = false;
      }
      else {
        AlertService.showAlert(AlertType.ERROR, "A version with these roles already exists.");
      }
    }
  }

  cancelNewAspect() {
    // reset form
    this.aspectToCopy = null;
    this.viewerRole = null;
    this.userRole = null;
    this.f.resetForm();
    this.modal = false;
  }

  saveAspects() {
    this.service.applyAspectChanges();
    this.service.selectedAspect = this.currentAspect;
    this.save.emit();
    ModalService.closeModal('manage-versions');
  }

  discardAspects() {
    this.discard.emit();
    ModalService.closeModal('manage-versions');
  }

  removeAspect(aspect: Aspect) {
    this.aspectsToEdit = this.aspectsToEdit.filter(e => !_.isEqual(e, aspect))
    this.service.aspectsToDelete.push(aspect);
    this.aspectToSelect = null;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isAspectSelected(aspect: Aspect) {
    return aspect === this.aspectToSelect;
  }

  getUnselectedAspects() {
    return this.aspectsToEdit.filter(e => !_.isEqual(e, this.currentAspect));
  }

  getAspectsAvailableForCopy() {
    return this.aspects.map(e => {
      return {
        value: (e.viewerRole ?? "none") + " | " + (e.userRole ?? "none"),
        text: "Viewer: " + (e.viewerRole ?? "none") + " | User: " + (e.userRole ?? "none")
      }
    });
  }
}
