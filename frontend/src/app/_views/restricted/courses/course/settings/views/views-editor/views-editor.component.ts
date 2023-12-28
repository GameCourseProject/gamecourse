import {Component, OnInit} from "@angular/core";
import {ActivatedRoute, Router} from "@angular/router";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {Page} from "src/app/_domain/views/pages/page";

import {initPageToManage, PageManageData} from "../views/views.component";
import { ViewType } from "src/app/_domain/views/view-types/view-type";
import { trigger, style, animate, transition, group } from '@angular/animations';
import { View, ViewMode } from "src/app/_domain/views/view";
import { buildView } from "src/app/_domain/views/build-view/build-view";
import * as _ from "lodash"
import { ViewSelectionService } from "src/app/_services/view-selection.service";
import { ModalService } from 'src/app/_services/modal.service';
import { AlertService, AlertType } from "src/app/_services/alert.service";
import { ViewBlock, ViewBlockDatabase } from "src/app/_domain/views/view-types/view-block";
import { User } from "src/app/_domain/users/user";
import { Aspect } from "src/app/_domain/views/aspects/aspect";
import { buildViewTree, getFakeId, groupedChildren, initGroupedChildren, selectedAspect, setSelectedAspect, viewsDeleted } from "src/app/_domain/views/build-view-tree/build-view-tree";
import { Role } from "src/app/_domain/roles/role";
import { Template } from "src/app/_domain/views/templates/template";
import { ViewCollapse, ViewCollapseDatabase } from "src/app/_domain/views/view-types/view-collapse";
import { ViewButtonDatabase, ViewButton } from "src/app/_domain/views/view-types/view-button";
import { ViewChartDatabase, ViewChart } from "src/app/_domain/views/view-types/view-chart";
import { ViewIconDatabase, ViewIcon } from "src/app/_domain/views/view-types/view-icon";
import { ViewImageDatabase, ViewImage } from "src/app/_domain/views/view-types/view-image";
import { ViewRowDatabase, ViewRow } from "src/app/_domain/views/view-types/view-row";
import { ViewTableDatabase, ViewTable } from "src/app/_domain/views/view-types/view-table";
import { ViewTextDatabase, ViewText } from "src/app/_domain/views/view-types/view-text";

export let viewsByAspect: { aspect: Aspect, view: View }[];
export let rolesHierarchy;

export function isMoreSpecific(role: string | null, antecessor: string | null): boolean {
  if (role && !antecessor || role === antecessor) {
    return true;
  }
  else if (rolesHierarchy[role]?.parent) {
    return isMoreSpecific(rolesHierarchy[role].parent._name, antecessor);
  }
  else {
    return false;
  }
}

@Component({
  selector: 'app-views-editor',
  templateUrl: './views-editor.component.html',
  animations: [
    trigger('dropdownAnimation', [
      transition(':enter', [
        style({
          transformOrigin: 'top',
          transform: 'scaleY(0.95)',
          opacity: 0,
        }),
        animate('70ms ease-out', style({ transform: 'scaleY(1)', opacity: 1 })),
      ]),
      transition(':leave', [
        group([
          animate('100ms ease-in', style({ opacity: 0 })),
          animate('100ms ease-in', style({ transform: 'scaleY(0.95)' })),
        ]),
      ]),
    ])
  ], // FIXME
})
export class ViewsEditorComponent implements OnInit {

  loading = {
    page: true,
    components: true,
    aspects: true,
    action: false
  };

  course: Course;                                 // Specific course in which page exists
  page: Page;                                     // page where information will be saved
  pageToManage: PageManageData;                   // Manage data
  template: Template;                             // template where information will be saved

  aspects: Aspect[];                              // Aspects saved
  aspectsToEdit: Aspect[];                        // Aspects currently being edited in modal
  aspectToSelect: Aspect;                         // Aspect selected in modal to switch to
  aspectToAdd: Aspect = new Aspect(null, null);   // New aspect

  coreComponents: any;
  customComponents: { id: number, view: View }[];
  sharedComponents: { id: number, sharedTimestamp: string, user: number, view: View }[];

  coreTemplates: { id: number, name: string, view: View }[];
  customTemplates: { id: number, name: string, view: View }[];
  sharedTemplates: { id: number, sharedTimestamp: string, user: number, view: View }[];

  user: User;

  previewMode: 'raw' | 'mock' | 'real' = 'raw';   // Preview mode selected to render

  options: Option[];
  activeSubMenu: SubMenu;

  newComponentName: string;                       // Name for custom component to be saved
  newTemplateName: string;                        // Name for custom template to be saved
  componentSettings: { id: number, top: number }; // Pop up for sharing/making private and deleting components
  templateSettings: { id: number, top: number };  // Pop up for sharing/making private and deleting templates

  view: View;                                     // Full view tree of the page

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    public selection: ViewSelectionService,
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getLoggedUser();
      await this.getComponents();
      await this.getTemplates();
      
      this.route.params.subscribe(async childParams => {
        const segmentForTemplate = this.route.snapshot.url[this.route.snapshot.url.length - 2].path;
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;
        
        if (segment === 'new') {
          // Prepare for creation
          this.pageToManage = initPageToManage(courseID);
          setSelectedAspect(new Aspect(null, null));
          this.aspectsToEdit = [selectedAspect];
          this.aspects = [selectedAspect];
          this.aspectToSelect = selectedAspect;
          this.loading.aspects = false;
          viewsByAspect = [{
            aspect: selectedAspect,
            view: buildView({
              id: getFakeId(),
              viewRoot: null,
              aspect: selectedAspect,
              type: "block",
              class: "p-2",
            })
          }];
          this.view = viewsByAspect[0].view;
          this.view.switchMode(ViewMode.EDIT);
          initGroupedChildren([]);
        }
        else if (segmentForTemplate === 'template') {
          await this.getTemplate(parseInt(segment));
          await this.getView();
        }
        else {
          await this.getPage(parseInt(segment));
          await this.getView();
        }

      });
      this.loading.page = false;
      this.componentSettings = { id: null, top: null };
      this.templateSettings = { id: null, top: null };
      this.setOptions();
    })
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();

    const rolesHierarchySmart = {};
    init(this.course.roleHierarchy, null);

    function init(rolesHierarchy: Role[], parent: Role) {
      for (const role of rolesHierarchy) {
        rolesHierarchySmart[role.name] = {role, parent, children: []};
        if (parent) rolesHierarchySmart[parent.name].children.push(role);

        // Traverse children
        if (role.children?.length > 0)
          init(role.children, role);
      }
    }
    rolesHierarchy = rolesHierarchySmart;
  }

  async getPage(pageID: number): Promise<void> {
    this.page = await this.api.getPageById(pageID).toPromise();
  }

  async getTemplate(templateID: number): Promise<void> {
    this.template = await this.api.getCustomTemplateById(templateID).toPromise();
  }

  async getView(): Promise<void> {
    let data;
    if (this.page) {
      data = await this.api.renderPageInEditor(this.page.id).toPromise();
    }
    else if (this.template) {
      data = await this.api.renderTemplateInEditor(this.template.id).toPromise();
    }
    else {
      AlertService.showAlert(AlertType.ERROR, 'Something went wrong...');
      return;
    }

    viewsByAspect = data["viewTreeByAspect"];
    initGroupedChildren(data["viewTree"]);
    this.view = viewsByAspect[0].view;
    this.view.switchMode(ViewMode.EDIT);
    
    this.aspects = viewsByAspect.map((e) => e.aspect);
    setSelectedAspect(this.aspects[0]);
    this.aspectToSelect = selectedAspect;
    this.aspectsToEdit = _.cloneDeep(this.aspects);
    this.loading.aspects = false;
  }

  async getComponents(): Promise<void> {
    this.coreComponents = await this.api.getCoreComponents().toPromise();
    this.customComponents = await this.api.getCustomComponents(this.course.id).toPromise();
    this.sharedComponents = await this.api.getSharedComponents().toPromise();
  }

  async getTemplates(): Promise<void> {
    this.coreTemplates = await this.api.getCoreTemplates(true).toPromise() as { id: number, name: string, view: View }[];
    this.customTemplates = await this.api.getCustomTemplates(this.course.id, true).toPromise() as { id: number, name: string, view: View }[];
    this.sharedTemplates = await this.api.getSharedTemplates(true).toPromise() as { id: number, sharedTimestamp: string, user: number, view: View }[];
  }

  setOptions() {
    this.loading.components = true;
    // Build views for core components
    // FIXME do this in api?
/*     const types = Object.keys(this.coreComponents);
    for (let type of types) {
      const categories = Object.keys(this.coreComponents[type]);
      for (let category of categories) {
        this.coreComponents[type][category] = this.coreComponents[type][category].map((e) => buildView(e))
      }
    }
 */
    // FIXME: move to backend maybe ?
    this.options =  [
      { icon: 'jam-plus-circle',
        iconSelected: 'jam-plus-circle-f',
        isSelected: false,
        description: 'Add Component',
        subMenu: {
          title: 'Components',
          items: [
            { title: ViewType.BLOCK,
              isSelected: false,
              helper: 'Component composed by other components.',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.BLOCK)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.BLOCK)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.BLOCK)
                }
              ]
            },
            { title: ViewType.BUTTON,
              isSelected: false,
              helper: 'Component that displays different types of buttons.',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.BUTTON)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.BUTTON)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.BUTTON)
                }
              ]
            },
            { title: ViewType.CHART,
              isSelected: false,
              helper: 'Component that displays different types of charts',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.CHART)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.CHART)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.CHART)
                }
              ]
            },
            { title: ViewType.COLLAPSE,
              isSelected: false,
              helper: 'Component that can hide or show other components.',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.COLLAPSE)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.COLLAPSE)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.COLLAPSE)
                }
              ]
            },
            { title: ViewType.ICON,
              isSelected: false,
              helper: 'Displays an icon.',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.ICON)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.ICON)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.ICON)
                }
              ]
            },
            { title: ViewType.IMAGE,
              isSelected: false,
              helper: 'Displays either simple static visual components or more complex ones built using expressions',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.IMAGE)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.IMAGE)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.IMAGE)
                }
              ]
            },
            { title: ViewType.TABLE,
              isSelected: false,
              helper: 'Displays a table with columns and rows. Can display a variable number of headers as well.',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.TABLE)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.TABLE)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.TABLE)
                }
              ]
            },
            { title: ViewType.TEXT,
              isSelected: false,
              helper: 'Displays either simple static written components or more complex ones built using expressions.',
              items: [
                { type: 'System',
                  isSelected: true,
                  helper: TypeHelper.SYSTEM,
                  list: this.coreComponents.get(ViewType.TEXT)
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: this.customComponents.filter((e) => e.view.type === ViewType.TEXT)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: this.sharedComponents.filter((e) => e.view.type === ViewType.TEXT)
                }
              ]
            },
          ]
      }},
      {
        icon: 'jam-layout',
        iconSelected: 'jam-layout-f',
        isSelected: false,
        description: 'Choose Template',
        subMenu: {
          title: 'Templates',
          isSelected: false,
          helper: 'Templates are final drafts of pages that have not been published yet. Its a layout of what a future page will look like.',
          items: [
            {
              type: 'System',
              isSelected: true,
              helper: TypeHelper.SYSTEM,
              list: this.coreTemplates
            },
            {
              type: 'Custom',
              isSelected: false,
              helper: TypeHelper.SYSTEM,
              list: this.customTemplates
            },
            {
              type: 'Shared',
              isSelected: false,
              helper: TypeHelper.SYSTEM,
              list: this.sharedTemplates
            },
          ]
        }
      },
      {
        icon: 'feather-move',
        iconSelected: 'feather-move',
        isSelected: false,
        description: 'Rearrange'
      }
    ];
    this.loading.components = false;
  }

  /*** ------------------------------------------------ ***/
  /*** -------------------- Actions ------------------- ***/
  /*** ------------------------------------------------ ***/

  selectOption(option: Option) {
    for (let i = 0; i < this.options.length; i++) {
      // if there's another option already selected
      if (this.options[i] !== option && this.options[i].isSelected) {
        this.options[i].isSelected = false;
        this.resetMenus();
        if (this.options[i].description == 'Rearrange') {
          this.selection.setRearrange(false);
        }
      }
    }

    // toggle selected option
    option.isSelected = !option.isSelected;

    // No menus active -> reset all
    if (!option.isSelected) {
      this.resetMenus();
      if (option.description == 'Rearrange') {
        this.selection.setRearrange(false);
      }
    }
    else if (option.description == 'Rearrange') {
      this.selection.setRearrange(true);
    }

    // since templates only have a submenu, switch that directly
    if (option.description === 'Choose Template') {
      option.subMenu.isSelected ? this.resetMenus : this.triggerSubMenu(option.subMenu, 0);
    }
  }

  triggerSubMenu(subMenu: SubMenu, index: number) {
    // make all other subMenus not selected
    let items = this.options[index].subMenu.items;
    for (const key in items){
      if (items[key] === subMenu) continue;
      items[key].isSelected = false;
    }

    // Trigger the selected subMenu
    subMenu.isSelected = !subMenu.isSelected;
    this.activeSubMenu = subMenu.isSelected ? subMenu : null;
  }

  async closeEditor() {
    ModalService.openModal("exit-management");
  }
  
  async closeConfirmed() {
    await this.router.navigate(['pages'], { relativeTo: this.route.parent });
  }

  addComponentToPage(item: View) {
    // All Aspects that should display the new item (this one and all others beneath in hierarchy)
    const toAdd = viewsByAspect.filter((e) =>
      (e.aspect.userRole === selectedAspect.userRole && isMoreSpecific(e.aspect.viewerRole, selectedAspect.viewerRole))
      || (e.aspect.userRole !== selectedAspect.userRole && isMoreSpecific(e.aspect.userRole, selectedAspect.userRole))
    );

    const fakeId = getFakeId();

    for (let el of toAdd) {
      let itemToAdd = _.cloneDeep(item);
      itemToAdd.mode = ViewMode.EDIT;
      itemToAdd.id = fakeId;
      itemToAdd.aspect = selectedAspect;
      itemToAdd.uniqueId = Math.round(Date.now() * Math.random());

      // Add child to the selected block
      if (this.selection.get()?.type === ViewType.BLOCK) {
        el.view.findView(this.selection.get().id)?.addChildViewToViewTree(itemToAdd);
      }
      // By default without valid selection add to existing root
      else {
        el.view.findView(this.view.id)?.addChildViewToViewTree(itemToAdd);
      }
    }

    if (this.selection.get()?.type === ViewType.BLOCK) {
      const originalGroup: number[][] = groupedChildren.get(this.selection.get().id) ?? [];
      originalGroup.push([fakeId]);
      groupedChildren.set(this.selection.get().id, originalGroup);
    }
    else {
      const originalGroup = groupedChildren.get(this.view.id) ?? [];
      originalGroup.push([fakeId]);
      groupedChildren.set(this.view.id, originalGroup);
    }

    this.view = viewsByAspect.find((e) => _.isEqual(e.aspect, selectedAspect))?.view;
    this.resetMenus();
  }

  addTemplateToPage(item: View) {
    let itemToAdd = _.cloneDeep(item);
    itemToAdd.switchMode(ViewMode.EDIT);

    // Page becomes the template
    if (!this.view) {
      this.view = itemToAdd;
    }
    // Add to the selected block
    else if (this.selection.get()?.type == ViewType.BLOCK) {
      this.selection.get().addChildViewToViewTree(itemToAdd);
    }
    // By default without valid selection add to existing root
    else {
      this.view.addChildViewToViewTree(itemToAdd);
    }

    this.resetMenus();
  }

  async savePage() {
    const buildedTree = buildViewTree(viewsByAspect.map((e) => e.view));
    //console.log(buildedTree);
    await this.api.saveViewAsPage(this.course.id, this.pageToManage.name, buildedTree).toPromise();
    await this.closeConfirmed();
    AlertService.showAlert(AlertType.SUCCESS, 'Page Created');
  }
  
  async saveChanges() {
    const buildedTree = buildViewTree(viewsByAspect.map((e) => e.view));
    //console.log(buildedTree);
    if (this.page) {
      await this.api.savePageChanges(this.course.id, this.page.id, buildedTree, viewsDeleted).toPromise();
      AlertService.showAlert(AlertType.SUCCESS, 'Changes Saved');
    }
    else if (this.template) {
      await this.api.saveTemplateChanges(this.course.id, this.template.id, buildedTree, viewsDeleted).toPromise();
      AlertService.showAlert(AlertType.SUCCESS, 'Changes Saved');
    }
    else {
      AlertService.showAlert(AlertType.ERROR, 'Something went wrong...');
    }
  }

  // Aspects -------------------------------------------------------

  selectAspect(aspect: Aspect) {
    this.aspectToSelect = aspect;
  }
  
  switchToAspect() {
    setSelectedAspect(this.aspectToSelect);
    const correspondentView = viewsByAspect.find((e) => _.isEqual(e.aspect, selectedAspect))?.view;
    if (correspondentView) {
      this.view = correspondentView;
      if (this.view) this.view.switchMode(ViewMode.EDIT);
    }
    else if (selectedAspect.userRole === null && selectedAspect.viewerRole === null) {
      this.view = null;
    }
    else {
      this.view = _.cloneDeep(viewsByAspect[0].view); // FIXME: should be view of the aspect most similar, less specific
      viewsByAspect.push({ aspect: selectedAspect, view: this.view });
    }
    ModalService.closeModal('manage-versions');
  }

  createNewAspect() {
    const aspect = new Aspect(null, null);
    this.aspectsToEdit.push(aspect);
    this.aspectToSelect = aspect;
  }

  submitAspects() {
    ModalService.closeModal('manage-versions');
    this.aspects = this.aspectsToEdit;
    this.aspectsToEdit = _.cloneDeep(this.aspects);
  }

  discardAspects() {
    ModalService.closeModal('manage-versions');
    this.aspectsToEdit = _.cloneDeep(this.aspects);
  }

  removeAspect(aspectIdx: number) {
    this.aspectsToEdit.splice(aspectIdx, 1);
    // TODO: should delete its view tree
    // TODO: when editing roles of a existing aspect should also traverse view tree to edit there
  }
  
  // Components -----------------------------------------------------

  async saveComponent() {
    let component = _.cloneDeep(this.selection.get());
    component.replaceWithFakeIds();

    // Don't save child components
    if (component instanceof ViewBlock) {
      component.children = [];
    }
    else if (component instanceof ViewCollapse) { 
      if (component.header instanceof ViewBlock) {
        component.header.children = [];
      }
      if (component.content instanceof ViewBlock) {
        component.content.children = [];
      }
    }

    await this.api.saveCustomComponent(this.course.id, this.newComponentName, buildComponent(component)).toPromise();
    ModalService.closeModal('save-as-component');
    AlertService.showAlert(AlertType.SUCCESS, 'Component saved successfully!');
    this.resetMenus();
    await this.getComponents();
    this.setOptions();
    this.newComponentName = "";
  }
  
  async shareComponent() {
    if (this.componentSettings.id) {
      await this.api.shareComponent(this.componentSettings.id, this.course.id, this.user.id, "").toPromise(); // FIXME description
      AlertService.showAlert(AlertType.SUCCESS, 'Component is now public!');
      this.resetMenus();
      await this.getComponents();
      this.setOptions();
    }
  }
  
  async makePrivateComponent() {
    if (this.componentSettings.id) {
      await this.api.makePrivateComponent(this.componentSettings.id, this.user.id).toPromise();
      AlertService.showAlert(AlertType.SUCCESS, 'Component is now private!');
      this.resetMenus();
      await this.getComponents();
      this.setOptions();
    }
  }
  
  async deleteComponent() {
    if (this.componentSettings.id) {
      await this.api.deleteCustomComponent(this.componentSettings.id, this.course.id).toPromise();
      AlertService.showAlert(AlertType.SUCCESS, 'Component deleted');
      this.resetMenus();
      await this.getComponents();
      this.setOptions();
    }
  }

  // Layouts --------------------------------------------------------

  async saveTemplate() {
    await this.api.saveCustomTemplate(this.course.id, this.newTemplateName, buildViewTree([this.view])).toPromise();
    ModalService.closeModal('save-template');
    AlertService.showAlert(AlertType.SUCCESS, 'Template saved successfully!');
    await this.closeConfirmed();
  }
  
  async shareTemplate() {
    if (this.templateSettings.id) {
      await this.api.shareTemplate(this.templateSettings.id, this.course.id, this.user.id, "").toPromise(); // FIXME description
      AlertService.showAlert(AlertType.SUCCESS, 'Template is now public!');
      this.resetMenus();
      await this.getTemplates();
      this.setOptions();
    }
  }
  
  async makePrivateTemplate() {
    if (this.templateSettings.id) {
      await this.api.makePrivateTemplate(this.templateSettings.id, this.user.id).toPromise();
      AlertService.showAlert(AlertType.SUCCESS, 'Template is now private!');
      this.resetMenus();
      await this.getTemplates();
      this.setOptions();
    }
  }
  
  async deleteTemplate() {
    if (this.templateSettings.id) {
      await this.api.deleteCustomTemplate(this.templateSettings.id, this.course.id).toPromise();
      AlertService.showAlert(AlertType.SUCCESS, 'Template deleted');
      this.resetMenus();
      await this.getTemplates();
      this.setOptions();
    }
  }

  // Previews -------------------------------------------------------

  async doActionPreview(action: string): Promise<void>{
    if (action === 'Manage versions') {
      ModalService.openModal('manage-versions');
    }
    else if (action === 'Undo') {
      console.log(this.view);
    }
    else if (action === 'Redo') {
    }
    else if (action === 'Raw (default)') {
      this.previewMode = 'raw';
      const correspondentView = viewsByAspect.find((e) => _.isEqual(e.aspect, selectedAspect))?.view;
      if (correspondentView) {
        this.view = correspondentView;
        if (this.view) this.view.switchMode(ViewMode.EDIT);
      }
    }
    else if (action === 'Layout preview (mock data)') {
      this.previewMode = 'mock';
      this.view = await this.api.renderPageWithMockData(this.page.id, selectedAspect).toPromise();
    }
/*     else if (action === 'Final preview (real data)') {
      this.previewMode = 'real';
      this.view = await this.api.previewPage(this.page.id, this.view.aspect).toPromise();
    } */
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getIcon(mode: string): string {
    if (mode === this.previewMode) return 'tabler-check';
    else return '';
  }

  getItems(option: Option) {
    return Object.keys(option.subMenu.items);
  }

  getCategoryListItem(): CategoryList[]{
    return this.activeSubMenu.items as CategoryList[];
  }

  resetMenus(){
    this.activeSubMenu = null;
    this.componentSettings = { id: null, top: null };
    this.templateSettings = { id: null, top: null };
    for (let i = 0; i < this.options.length; i++){
      this.options[i].isSelected = false;
      if (this.options[i].subMenu) this.options[i].subMenu.isSelected = false;

      for (let j = 0; j < this.options[i].subMenu?.items.length; j++){
        this.options[i].subMenu.items[j].isSelected = false;
      }
    }
  }

  selectCategory(index: number){
    // reset all categories previously selected
    let items = this.activeSubMenu.items
    for (let i = 0; i < items.length; i ++){
      if (i === index) continue;
      this.activeSubMenu.items[i].isSelected = false;
    }

    // reset component and template pop ups
    this.componentSettings = { id: null, top: null };
    this.templateSettings = { id: null, top: null };

    // toggle selected category
    this.activeSubMenu.items[index].isSelected = !this.activeSubMenu.items[index].isSelected;
  }

  getSelectedCategories() {
    return (this.activeSubMenu.items as CategoryList[]).filter((item) => item.isSelected);
  }
  
  getSelectedCategoriesItems() {
    return this.getSelectedCategories().flatMap((category) => category.list);
  }

  getSubcategories() {
    return this.getSelectedCategories()[0]['list'];
  }

  getComponentsOfSubcategory(subcategory: string) {
    return this.getSelectedCategories()[0]['list'][subcategory];
  }

  openSaveAsPageModal() {
    ModalService.openModal('save-page');
  }

  openSaveAsTemplateModal() {
    ModalService.openModal('save-template');
  }

  triggerComponentSettings(event: MouseEvent, componentId: number) {
    this.componentSettings.id = this.componentSettings.id == componentId ? null : componentId;
    this.componentSettings.top = event.pageY - 325;
  }

  triggerTemplateSettings(event: MouseEvent, templateId: number) {
    this.templateSettings.id = this.templateSettings.id == templateId ? null : templateId;
    this.templateSettings.top = event.pageY - 325;
  }

  getSelectedAspect() {
    return selectedAspect;
  }

  isAspectSelected(aspect: Aspect) {
    return aspect === this.aspectToSelect;
  }
  
}

export interface Option {
  icon: string,
  iconSelected: string,
  isSelected: boolean,
  description: string,
  subMenu?: SubMenu
}

export interface SubMenu {
  title: string,
  isSelected?: boolean,
  helper?: string,
  items: SubMenu[] | CategoryList[]
}

export interface CategoryList {
  type: 'System' | 'Custom' | 'Shared',
  isSelected: boolean,
  helper?: TypeHelper,
  list: any | { category: string, items: any[] }
}

export enum TypeHelper {
  SYSTEM = 'System components are provided by GameCourse and already configured and ready for use.',
  CUSTOM = 'Custom components are created by users in this course.',
  SHARED = 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses.'
}

export function buildComponent(view: View): ViewBlockDatabase[] | ViewButtonDatabase[] | ViewChartDatabase[]
    | ViewCollapseDatabase[] | ViewIconDatabase[] | ViewImageDatabase[] | ViewRowDatabase[] | ViewTableDatabase[] | ViewTextDatabase[] {
  const type = view.type;

  if (type === ViewType.BLOCK) return [ViewBlock.toDatabase(view as ViewBlock)];
  else if (type === ViewType.BUTTON) return [ViewButton.toDatabase(view as ViewButton)];
  else if (type === ViewType.CHART) return [ViewChart.toDatabase(view as ViewChart)];
  else if (type === ViewType.COLLAPSE) return [ViewCollapse.toDatabase(view as ViewCollapse)];
  else if (type === ViewType.ICON) return [ViewIcon.toDatabase(view as ViewIcon)];
  else if (type === ViewType.IMAGE) return [ViewImage.toDatabase(view as ViewImage)];
  else if (type === ViewType.ROW) return [ViewRow.toDatabase(view as ViewRow)];
  else if (type === ViewType.TABLE) return [ViewTable.toDatabase(view as ViewTable)];
  else if (type === ViewType.TEXT) return [ViewText.toDatabase(view as ViewText)];
  // NOTE: insert here other types of building-blocks

  return null;
}