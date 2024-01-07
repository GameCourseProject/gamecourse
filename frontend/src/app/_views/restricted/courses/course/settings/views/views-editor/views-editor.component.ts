import {Component, OnDestroy, OnInit} from "@angular/core";
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
import { buildViewTree, getFakeId, groupedChildren, initGroupedChildren, setGroupedChildren, viewsDeleted } from "src/app/_domain/views/build-view-tree/build-view-tree";
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
import html2canvas from "html2canvas";
import { HistoryService } from "src/app/_services/history.service";
import { ViewEditorService } from "src/app/_services/view-editor.service";

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
export class ViewsEditorComponent implements OnInit, OnDestroy {

  loading = {
    page: true,
    components: true,
    aspects: true,
    action: false
  };

  view: View;                                     // Full view tree of the page
  editable?: boolean = true;                      // If true, can modify the view, else it's essentially just a preview
  previewMode: 'raw' | 'mock' | 'real' = 'raw';   // Preview mode selected to render

  course: Course;                                 // Specific course in which page exists
  user: User;                                     // Logged in user
  page: Page;                                     // page where information will be saved
  pageToManage: PageManageData;                   // Manage data
  template: Template;                             // template where information will be saved
  coreTemplate: Template;                         // core template to view

  aspects: Aspect[];                              // Aspects saved
  aspectsToEdit: Aspect[];                        // Aspects currently being edited in modal
  aspectToSelect: Aspect;                         // Aspect selected in modal to switch to

  options: Option[];
  activeSubMenu: SubMenu;

  coreComponents: any;
  customComponents: { id: number, view: View }[];
  sharedComponents: { id: number, sharedTimestamp: string, user: number, view: View }[];

  coreTemplates: { id: number, name: string, view: View }[];
  customTemplates: { id: number, name: string, view: View }[];
  sharedTemplates: { id: number, name: string, sharedTimestamp: string, user: number, view: View }[];

  newComponentName: string;                       // Name for custom component to be saved
  newTemplateName: string;                        // Name for custom template to be saved
  componentSettings: { id: number, top: number }; // Pop up for sharing/making private and deleting components
  templateSettings: { id: number, top: number };  // Pop up for sharing/making private and deleting templates

  templateToAdd: any;                                      // Template that will be added, after user selects by value or by reference in the modal

  // ADD TEMPLATE OPTIONS
  duplicateOptions: {name: string, char: string}[] = [     
    {name: "By reference", char: "ref"},
    {name: "By value", char: "value"}
  ];
  optionSelected: "ref" | "value" = null;

  _subscription;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private selection: ViewSelectionService,
    public history: HistoryService,
    public service: ViewEditorService
  ) { }

  ngOnInit(): void {
    this.history.clear();
    this._subscription = this.service.selectedChange.subscribe((value) => { 
      this.view = null;
      setTimeout(() => {
        this.view = value;
      }, 100);
    });
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getLoggedUser();
      await this.getComponents();
      await this.getTemplates();
      
      this.route.params.subscribe(async childParams => {
        const segmentForTemplate = this.route.snapshot.url[this.route.snapshot.url.length - 2].path;
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;
        this.selection.setRearrange(false);
        
        if (segment === 'new') {
          // Prepare for creation
          this.pageToManage = initPageToManage(courseID);
          this.service.selectedAspect = new Aspect(null, null);
          this.aspectsToEdit = [this.service.selectedAspect];
          this.aspects = [this.service.selectedAspect];
          this.aspectToSelect = this.service.selectedAspect;
          this.loading.aspects = false;
          this.service.viewsByAspect = [{
            aspect: this.service.selectedAspect,
            view: buildView({
              id: getFakeId(),
              viewRoot: null,
              aspect: this.service.selectedAspect,
              type: "block",
              class: "p-2",
            })
          }];
          this.view = this.service.viewsByAspect[0].view;
          if (this.editable) this.view.switchMode(ViewMode.EDIT);
          initGroupedChildren([]);
          this.history.saveState({
            viewsByAspect: this.service.viewsByAspect,
            groupedChildren: groupedChildren
          });
        }
        else if (segmentForTemplate === 'template') {
          await this.getTemplate(parseInt(segment));
          await this.getView();
          this.history.saveState({
            viewsByAspect: this.service.viewsByAspect,
            groupedChildren: groupedChildren
          });
        }
        else if (segmentForTemplate === 'system-template') {
          this.editable = false;
          await this.getCoreTemplate(parseInt(segment));
          await this.getView();
          this.history.saveState({
            viewsByAspect: this.service.viewsByAspect,
            groupedChildren: groupedChildren
          });
        }
        else {
          await this.getPage(parseInt(segment));
          await this.getView();
          this.history.saveState({
            viewsByAspect: this.service.viewsByAspect,
            groupedChildren: groupedChildren
          });
        }

      });
      this.loading.page = false;
      this.componentSettings = { id: null, top: null };
      this.templateSettings = { id: null, top: null };
      this.setOptions();
    })
  }

  ngOnDestroy() {
    this._subscription.unsubscribe();
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
    this.service.rolesHierarchy = rolesHierarchySmart;
  }

  async getPage(pageID: number): Promise<void> {
    this.page = await this.api.getPageById(pageID).toPromise();
  }

  async getTemplate(templateID: number): Promise<void> {
    this.template = await this.api.getCustomTemplateById(templateID).toPromise();
  }

  async getCoreTemplate(templateID: number): Promise<void> {
    this.coreTemplate = await this.api.getCoreTemplateById(templateID).toPromise();
  }

  async getView(): Promise<void> {
    let data;
    if (this.page) {
      data = await this.api.renderPageInEditor(this.page.id).toPromise();
    }
    else if (this.template) {
      data = await this.api.renderCustomTemplateInEditor(this.template.id).toPromise();
    }
    else if (this.coreTemplate) {
      data = await this.api.renderCoreTemplateInEditor(this.coreTemplate.id, this.course.id).toPromise();
    }
    else {
      AlertService.showAlert(AlertType.ERROR, 'Something went wrong...');
      return;
    }

    this.service.viewsByAspect = data["viewTreeByAspect"];
    initGroupedChildren(data["viewTree"]);
    this.view = this.service.viewsByAspect[0].view;
    
    if (this.editable) this.view.switchMode(ViewMode.EDIT);
    
    this.aspects = this.service.viewsByAspect.map((e) => e.aspect);
    this.service.selectedAspect = this.aspects[0];
    this.aspectToSelect = this.service.selectedAspect;
    this.aspectsToEdit = _.cloneDeep(this.aspects);
    this.loading.aspects = false;
  }

  async getComponents(): Promise<void> {
    this.coreComponents = await this.api.getCoreComponents().toPromise();
    this.customComponents = await this.api.getCustomComponents(this.course.id).toPromise();
    this.sharedComponents = await this.api.getSharedComponents().toPromise();
  }

  async getTemplates(): Promise<void> {
    this.coreTemplates = await this.api.getCoreTemplates(this.course.id, true).toPromise() as { id: number, name: string, view: View }[];
    this.customTemplates = await this.api.getCustomTemplates(this.course.id, true).toPromise() as { id: number, name: string, view: View }[];
    this.sharedTemplates = await this.api.getSharedTemplates(true).toPromise() as { id: number, name: string, sharedTimestamp: string, user: number, view: View }[];
  }

  setOptions() {
    this.loading.components = true;

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
    console.log(this.service.viewsByAspect);
    if (this.history.hasUndo()) {
      ModalService.openModal("exit-management");
    }
    else {
      this.closeConfirmed();
    }
  }
  
  async closeConfirmed() {
    await this.router.navigate(['pages'], { relativeTo: this.route.parent });
  }

  addComponentToPage(item: View) {
    // Add child to the selected block
    if (this.selection.get()?.type === ViewType.BLOCK) {
      this.service.add(item, this.selection.get(), "value");
    }
    // If the page is empty, need to add a block first
    else if (!this.service.getSelectedView() && item.type !== ViewType.BLOCK) {
      this.view = null;
      const block = buildView({
        id: getFakeId(),
        viewRoot: null,
        aspect: this.service.selectedAspect,
        type: "block",
        class: "p-2",
      });
      block.addChildViewToViewTree(item);
      block.switchMode(ViewMode.EDIT);
      this.service.add(block, null, "value");
    }
    // By default without valid selection add to existing root
    else {
      this.service.add(item, this.view, "value");
    }
    this.history.saveState({
      viewsByAspect: this.service.viewsByAspect,
      groupedChildren: groupedChildren
    });
    this.resetMenus();
  }
  
  addTemplateToPage(item: View) {
    // Add child to the selected block
    if (this.selection.get()?.type === ViewType.BLOCK) {
      this.service.add(item, this.selection.get(), this.optionSelected);
    }
    // If the page is empty, template becomes the root
    else if (!this.service.getSelectedView()) {
      this.service.add(item, null, this.optionSelected);
    }
    // By default without valid selection add to existing root
    else {
      this.service.add(item, this.view, this.optionSelected);
    }
    this.history.saveState({
      viewsByAspect: this.service.viewsByAspect,
      groupedChildren: groupedChildren
    });

    this.optionSelected = null;
    ModalService.closeModal("add-template");
    this.resetMenus();
  }

  async savePage() {
    const buildedTree = buildViewTree(this.service.viewsByAspect.map((e) => e.view));

    console.log(buildedTree);
    console.log(groupedChildren);
    
    const image = await this.takeScreenshot();
    await this.api.saveViewAsPage(this.course.id, this.pageToManage.name, buildedTree, image).toPromise(); // FIXME null -> image
    await this.closeConfirmed();
    AlertService.showAlert(AlertType.SUCCESS, 'Page Created');
  }
  
  async saveChanges() {
    const buildedTree = buildViewTree(this.service.viewsByAspect.map((e) => e.view));

    console.log(this.service.viewsByAspect);
    console.log(buildedTree);

    const image = await this.takeScreenshot();
    if (this.page) {
      await this.api.savePageChanges(this.course.id, this.page.id, buildedTree, viewsDeleted, image).toPromise();
      await this.closeConfirmed();
      AlertService.showAlert(AlertType.SUCCESS, 'Changes Saved');
    }
    else if (this.template) {
      await this.api.saveTemplateChanges(this.course.id, this.template.id, buildedTree, viewsDeleted, image).toPromise();
      await this.closeConfirmed();
      AlertService.showAlert(AlertType.SUCCESS, 'Changes Saved');
    }
    else {
      AlertService.showAlert(AlertType.ERROR, 'Something went wrong...');
    }
  }

  async takeScreenshot() {
    return await html2canvas(document.querySelector("#capture")).then(canvas => {
      return canvas.toDataURL('image/png');
    });
  }

  // Aspects -------------------------------------------------------

  selectAspect(aspect: Aspect) {
    this.aspectToSelect = aspect;
  }
  
  switchToAspect() {
    this.saveAspects();
    this.service.selectedAspect = this.aspectToSelect;
    const correspondentView = this.service.getSelectedView();
    if (correspondentView) {
      this.view = correspondentView;
      if (this.view && this.editable) this.view.switchMode(ViewMode.EDIT);
    }
    this.previewMode = 'raw';
    ModalService.closeModal('manage-versions');
  }

  createNewAspect() {
    const aspect = new Aspect("new", "new");
    this.aspectsToEdit.push(aspect);
    this.service.aspectsToAdd.push(aspect);
    this.aspectToSelect = aspect;
  }

  submitAspects() {
    this.saveAspects();
    console.log(this.service.viewsByAspect);
    ModalService.closeModal('manage-versions');
  }

  saveAspects() {
    this.aspects = this.aspectsToEdit;
    this.aspectsToEdit = _.cloneDeep(this.aspects);
    this.service.applyAspectChanges();
  }

  discardAspects() {
    ModalService.closeModal('manage-versions');
    this.aspectsToEdit = _.cloneDeep(this.aspects);
    this.service.aspectsToDelete = [];
    this.service.aspectsToChange = [];
    this.service.aspectsToAdd = [];
  }

  removeAspect(aspectIdx: number) {
    const deleted = this.aspectsToEdit.splice(aspectIdx, 1);
    this.service.aspectsToDelete.push(deleted[0]);
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

  // Templates --------------------------------------------------------

  async saveTemplate() {
    const image = await this.takeScreenshot();
    await this.api.saveCustomTemplate(this.course.id, this.newTemplateName, buildViewTree([this.view]), image).toPromise();
    await this.closeConfirmed();
    AlertService.showAlert(AlertType.SUCCESS, 'Template saved successfully!');
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
      if (this.history.hasUndo()) {
        const res = this.history.undo();
        this.service.viewsByAspect = res.viewsByAspect;
        setGroupedChildren(res.groupedChildren);
        this.view = this.service.getSelectedView();
      }
    }
    else if (action === 'Redo') {
      if (this.history.hasRedo()) {
        const res = this.history.redo();
        this.service.viewsByAspect = res.viewsByAspect;
        setGroupedChildren(res.groupedChildren);
        this.view = this.service.getSelectedView();
      }
    }
    else if (action === 'Raw (default)') {
      this.previewMode = 'raw';
      const correspondentView = this.service.getSelectedView();
      if (correspondentView) {
        this.view = correspondentView;
        if (this.view && this.editable) this.view.switchMode(ViewMode.EDIT);
      }
    }
    else if (action === 'Layout preview (mock data)') {
      this.previewMode = 'mock';
      this.view = await this.api.renderPageWithMockData(this.page.id, this.service.selectedAspect).toPromise();
    }
/*     else if (action === 'Final preview (real data)') {
      this.previewMode = 'real';
      this.view = await this.api.previewPage(this.page.id, this.view.aspect).toPromise();
    } */
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  get ViewType(): typeof ViewType {
    return ViewType;
  }

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

  openAddTemplateModal(item: any) {
    this.templateToAdd = item;
    ModalService.openModal('add-template');
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
  else if (type === ViewType.ROW) return [ViewRow.toDatabase(view as ViewRow, true)];
  else if (type === ViewType.TABLE) return [ViewTable.toDatabase(view as ViewTable, true)];
  else if (type === ViewType.TEXT) return [ViewText.toDatabase(view as ViewText)];
  // NOTE: insert here other types of building-blocks

  return null;
}