import {Component, OnDestroy, OnInit} from "@angular/core";
import {ActivatedRoute, Router} from "@angular/router";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {Page} from "src/app/_domain/views/pages/page";
import {initPageToManage, PageManageData} from "../views/views.component";
import {ViewType} from "src/app/_domain/views/view-types/view-type";
import {View, ViewMode} from "src/app/_domain/views/view";
import {buildView} from "src/app/_domain/views/build-view/build-view";
import * as _ from "lodash";
import {ViewSelectionService} from "src/app/_services/view-selection.service";
import {ModalService} from 'src/app/_services/modal.service';
import {AlertService, AlertType} from "src/app/_services/alert.service";
import {ViewBlock, ViewBlockDatabase} from "src/app/_domain/views/view-types/view-block";
import {User} from "src/app/_domain/users/user";
import {Aspect} from "src/app/_domain/views/aspects/aspect";
import {
  buildViewTree,
  getFakeId,
  groupedChildren,
  initGroupedChildren,
  setGroupedChildren, setViewsDeleted,
  viewsDeleted
} from "src/app/_domain/views/build-view-tree/build-view-tree";
import {Role} from "src/app/_domain/roles/role";
import {Template} from "src/app/_domain/views/templates/template";
import {ViewCollapse, ViewCollapseDatabase} from "src/app/_domain/views/view-types/view-collapse";
import {ViewButton, ViewButtonDatabase} from "src/app/_domain/views/view-types/view-button";
import {ViewChart, ViewChartDatabase} from "src/app/_domain/views/view-types/view-chart";
import {ViewIcon, ViewIconDatabase} from "src/app/_domain/views/view-types/view-icon";
import {ViewImage, ViewImageDatabase} from "src/app/_domain/views/view-types/view-image";
import {ViewRow, ViewRowDatabase} from "src/app/_domain/views/view-types/view-row";
import {ViewTable, ViewTableDatabase} from "src/app/_domain/views/view-types/view-table";
import {ViewText, ViewTextDatabase} from "src/app/_domain/views/view-types/view-text";
import {HistoryEntry, HistoryService} from "src/app/_services/history.service";
import {ViewEditorService} from "src/app/_services/view-editor.service";
import {Subscription} from "rxjs";
import {HttpErrorResponse} from "@angular/common/http";
import {domToPng} from 'modern-screenshot'
import {ErrorService} from "../../../../../../../_services/error.service";

@Component({
  selector: 'app-views-editor',
  templateUrl: './views-editor.component.html',
  styleUrls: ['./views-editor.component.scss']
})
export class ViewsEditorComponent implements OnInit, OnDestroy {

  loading = {
    page: true,
    components: true,
    action: false,
    users: true
  };

  view: View;                                     // Full view tree of the page
  editable?: boolean = true;                      // If true, can modify the view, else it's essentially just a preview
  previewMode: 'raw' | 'mock' | 'real' = 'raw';   // Preview mode selected to render

  course: Course;                                 // Specific course in which page exists
  user: User;                                     // Logged in user
  page: Page;                                     // page where information will be saved
  pageToManage: PageManageData;                   // NEW page where information will be saved, and also used for NEW template NAME
  template: Template;                             // template where information will be saved
  templateNameToManage: string;                   // NEW template name
  coreTemplate: Template;                         // core template to view

  aspects: Aspect[];                              // Aspects saved
  manageAspects: boolean = false;

  usersToPreview: { value: number, text: string }[];
  viewersToPreview: { value: number, text: string }[];
  userToPreview: number;
  viewerToPreview: number;

  options: Option[];
  activeSubMenu: SubMenu;

  showToast: boolean = false;                   // Tutorial Modal
  videoElement: HTMLVideoElement | null = null;

  coreComponents: Map<ViewType, { category: string; views: View[] }[]>;
  customComponents: { id: number, view: View }[];
  sharedComponents: { id: number, sharedTimestamp: string, user: number, view: View }[];

  coreTemplates: { id: number, name: string, view: View }[];
  customTemplates: { id: number, name: string, view: View }[];
  sharedTemplates: { id: number, name: string, sharedTimestamp: string, user: number, view: View }[];

  newComponentName: string;                       // Name for custom component to be saved
  componentSettings: { id: number, top: number }; // Pop up for sharing/making private and deleting components
  templateSettings: { id: number, top: number };  // Pop up for sharing/making private and deleting templates

  templateToAdd: any;                             // Template that will be added, after user selects by value or by reference in the modal

  duplicateOptions: { name: string, char: string }[] = [
    {name: "By reference", char: "ref"},
    {name: "By value", char: "value"}
  ];
  optionSelected: "ref" | "value" = null;         // mode of adding the Template

  _subscription: Subscription;

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
    this.selection.clear();
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

      this.route.params.subscribe(async () => {
        const prevSegment = this.route.snapshot.url[this.route.snapshot.url.length - 2].path;
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;

        if (segment === 'new') {
          this.pageToManage = initPageToManage(courseID);
          this.service.selectedAspect = new Aspect(null, null);
          this.aspects = [this.service.selectedAspect];
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
          this.view.switchMode(ViewMode.EDIT);
          initGroupedChildren([]);
          this.history.saveState({
            viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
            groupedChildren: groupedChildren,
            viewsDeleted: viewsDeleted
          });
        }
        else {
          await this.initView(parseInt(segment),
            (prevSegment === "template" || prevSegment === "system-template") ? prevSegment : null);
        }
      });

      this.loading.page = false;
      this.componentSettings = { id: null, top: null };
      this.templateSettings = { id: null, top: null };
      this.setOptions();
    })

    this.handleKeyDown = this.handleKeyDown.bind(this);
    addEventListener('keydown', this.handleKeyDown);
  }

  ngOnDestroy() {
    removeEventListener('keydown', this.handleKeyDown);
    this._subscription.unsubscribe();
  }

  async handleKeyDown(event: KeyboardEvent) {
    if (ModalService.isOpen("component-editor")) return;

    if (((event.key === 'Z' || event.key === 'z') && (event.ctrlKey || event.metaKey) && event.shiftKey) ||
      (event.key === 'Y' || event.key === 'y') && event.ctrlKey)
    {
      event.preventDefault();
      await this.doAction('Redo');
    }
    else if ((event.key === 'Z' || event.key === 'z') && (event.ctrlKey || event.metaKey)) {
      event.preventDefault();
      await this.doAction('Undo');
    }
    else if (event.ctrlKey && (event.key === 'S' || event.key === 's')) {
      event.preventDefault();
      if (this.page || this.template) { await this.saveChanges(); }
      else if (this.pageToManage) { this.openSaveAsPageModal(); }
    }
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

  async initView(id: number, templateType?: 'template' | 'system-template', aspect?: Aspect): Promise<void> {
    let data;
    if (!templateType) {
      this.page = await this.api.getPageById(id).toPromise();
      data = await this.api.renderPageInEditor(this.page.id).toPromise();
    }
    else if (templateType === 'template') {
      this.template = await this.api.getCustomTemplateById(id).toPromise();
      data = await this.api.renderCustomTemplateInEditor(this.template.id).toPromise();
    }
    else if (templateType === 'system-template') {
      this.editable = false;
      this.coreTemplate = await this.api.getCoreTemplateById(id).toPromise();
      data = await this.api.renderCoreTemplateInEditor(this.coreTemplate.id, this.course.id).toPromise();
    }
    else {
      AlertService.showAlert(AlertType.ERROR, 'Error: Couldn\'t identify as a valid template type (should be either system or custom)');
      return;
    }

    this.service.viewsByAspect = data["viewTreeByAspect"];
    initGroupedChildren(data["viewTree"]);

    this.aspects = this.service.viewsByAspect.map((e) => e.aspect);

    if (!aspect) this.service.selectedAspect = this.aspects[0];
    else this.service.selectedAspect = aspect;

    this.sortAspects();

    this.view = this.service.getSelectedView();
    if (this.view && this.editable) this.view.switchMode(ViewMode.EDIT);

    this.history.clear();
    this.history.saveState({
      viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
      groupedChildren: groupedChildren,
      viewsDeleted: viewsDeleted
    });
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
      }
    }

    // toggle selected option
    option.isSelected = !option.isSelected;

    // No menus active -> reset all
    if (!option.isSelected) {
      this.resetMenus();
    }

    // since templates only have a submenu, switch that directly
    if (option.description === 'Choose Template') {
      if (option.subMenu.isSelected) this.resetMenus();
      else this.triggerSubMenu(option.subMenu, 0);
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
    if (this.history.hasUndo()) {
      ModalService.openModal("exit-management");
    } else {
      await this.closeConfirmed();
    }
  }

  async closeConfirmed() {
    await this.router.navigate(['pages'], { relativeTo: this.route.parent });
  }

  addComponentToPage(item: View) {
    const selected = this.selection.get();

    // Add child to the selected block
    if (selected?.type === ViewType.BLOCK) {
      this.service.add(item, selected, "value");
    } else if (selected) {
      this.service.add(item, selected.parent, "value");
    }
    // If the page is empty, need to add a block first
    else if (!this.service.getSelectedView()) {
      if (item.type !== ViewType.BLOCK) {
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
      else {
        this.service.add(item, null, "value");
      }
    }
    // By default, without valid selection add to existing root
    else {
      this.service.add(item, this.view, "value");
    }
    this.history.saveState({
      viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
      groupedChildren: groupedChildren,
      viewsDeleted: viewsDeleted
    });
    this.resetMenus();
  }

  addTemplateToPage(item: View) {
    if (!this.optionSelected) {
      AlertService.showAlert(AlertType.ERROR, "You must choose the way you want to add the template.");
      return;
    }

    this.loading.action = true;

    // Add child to the selected block
    if (this.selection.get()?.type === ViewType.BLOCK) {
      this.service.add(item, this.selection.get(), this.optionSelected);
    }
    // If the page is empty, template becomes the root
    else if (!this.service.getSelectedView()) {
      this.service.add(item, null, this.optionSelected);
    }
    // By default, without valid selection add to existing root
    else {
      this.service.add(item, this.view, this.optionSelected);
    }
    this.history.saveState({
      viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
      groupedChildren: groupedChildren,
      viewsDeleted: viewsDeleted
    });

    this.optionSelected = null;
    ModalService.closeModal("add-template");
    this.resetMenus();

    this.loading.action = false;
  }

  async savePage(): Promise<void | "error"> {
    AlertService.clear(AlertType.ERROR);

    if (!this.pageToManage.name) {
      AlertService.showAlert(AlertType.ERROR, "The page must have a name.");
      return;
    }

    this.loading.action = true;

    let buildedTree;
    try {
      buildedTree = buildViewTree(this.service.viewsByAspect.map((e) => e.view));
    } catch (e) {
      AlertService.showAlert(AlertType.ERROR, "Error: Something went wrong while building the tree to be saved. This is most likely a bug. Contact an admin or try a different page.");
      this.loading.action = false;
      return "error";
    }

    let image;
    try {
      image = await this.takeScreenshot();
    } catch {
      image = null;
    }

    try {
      const pageId = await this.api.saveViewAsPage(this.course.id, this.pageToManage.name, buildedTree, image).toPromise();
      this.pageToManage = null;
      const aspect = this.service.selectedAspect;

      this.loading.page = true;

      await this.router.navigate(['/courses/' + this.course.id + '/settings/pages/editor/' + pageId]);
      await this.initView(pageId, null, aspect);

      AlertService.showAlert(AlertType.SUCCESS, 'Page Created');
    }
    catch (e) {
      this.recoverFromFail();
      ModalService.closeModal('save-page');
      this.loading.action = false;
      return "error";
    }

    this.loading.page = false;
    this.loading.action = false;
  }

  async saveChanges(): Promise<void | "error"> {
    AlertService.clear(AlertType.ERROR);

    if (this.page && !this.page.name) {
      AlertService.showAlert(AlertType.ERROR, "The page must have a name.");
      return;
    } else if (this.template && !this.template.name) {
      AlertService.showAlert(AlertType.ERROR, "The template must have a name.")
      return;
    }

    this.loading.action = true;

    let buildedTree;
    try {
      buildedTree = buildViewTree(this.service.viewsByAspect.map((e) => e.view));
    } catch (e) {
      AlertService.showAlert(AlertType.ERROR, "Error: Something went wrong while building the tree to be saved. This is most likely a bug. Contact an admin or try a different page.");
      this.loading.action = false;
      return "error";
    }

    let image;
    try {
      image = await this.takeScreenshot();
    } catch {
      image = null;
    }

    try {
      if (this.page) {
        await this.api.savePageChanges(this.course.id, this.page.id, buildedTree, viewsDeleted, this.page.name, image).toPromise();
        await this.initView(this.page.id, null, this.service.selectedAspect);
        AlertService.showAlert(AlertType.SUCCESS, 'Changes Saved');
      }
      else if (this.template) {
        await this.api.saveTemplateChanges(this.course.id, this.template.id, buildedTree, viewsDeleted, this.template.name, image).toPromise();
        await this.initView(this.template.id, "template", this.service.selectedAspect);
        AlertService.showAlert(AlertType.SUCCESS, 'Changes Saved');
      }
      else {
        AlertService.showAlert(AlertType.ERROR, 'Error: Couldn\'t detect if this is a page or a template');
      }

      this.loading.action = false;
    }
    catch (e) {
      this.recoverFromFail();
      this.loading.action = false;
      return "error";
    }

  }

  async takeScreenshot() {
    return await domToPng(document.querySelector("#capture"))
      .then(dataURL => {
        return dataURL;
      });
  }

  // ---------------------------------------------------------------
  // Aspects -------------------------------------------------------
  // ---------------------------------------------------------------

  sortAspects() {
    this.aspects = this.service.viewsByAspect.map((e) => e.aspect).sort((a, b) => {
      if (_.isEqual(a, new Aspect(null, null))) return -1;
      else if (_.isEqual(b, new Aspect(null, null))) return 1;
      else if (a.viewerRole == null && b.viewerRole != null) return 1;
      else if (a.viewerRole != null && b.viewerRole == null) return -1;
      else if (a.viewerRole != b.viewerRole) return this.service.isMoreSpecific(a.viewerRole, b.viewerRole) ? 1 : -1;
      else return !this.service.isMoreSpecific(a.userRole, b.userRole) ? -1 : 1;
    });
  }

  discardAspects() {
    this.manageAspects = false;
  }

  saveAspects() {
    this.aspects = this.service.viewsByAspect.map((e) => e.aspect);
    this.view = this.service.getSelectedView();
    if (this.view && this.editable) this.view.switchMode(ViewMode.EDIT);
    this.previewMode = 'raw';

    this.sortAspects();
    this.selection.clear();
  }

  switchToAspect(aspect: Aspect) {
    this.service.selectedAspect = aspect;
    this.view = this.service.getSelectedView();

    if (this.view && this.editable) {
      this.view.switchMode(ViewMode.EDIT);
      this.previewMode = 'raw';
    }

    this.manageAspects = false;
    this.selection.clear();
  }

  aspectIsSelected(aspect: Aspect) {
    return _.isEqual(this.service.selectedAspect, aspect);
  }


  // ----------------------------------------------------------------
  // Components -----------------------------------------------------
  // ----------------------------------------------------------------

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
    ModalService.closeModal('save-component');
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

  // ----------------------------------------------------------------
  // Templates ------------------------------------------------------
  // ----------------------------------------------------------------

  async saveTemplate() {
    if ((this.pageToManage && !this.pageToManage.name) || (!this.pageToManage && !this.templateNameToManage)) {
      AlertService.showAlert(AlertType.ERROR, "The template must have a name.");
      return;
    }

    this.loading.action = true;

    let image;
    try {
      image = await this.takeScreenshot();
    } catch {
      image = null;
    }

    try {
      await this.api.saveCustomTemplate(
        this.course.id,
        this.pageToManage ? this.pageToManage.name : this.templateNameToManage,
        buildViewTree([this.view]),
        image
      ).toPromise();
      await this.closeConfirmed();
      AlertService.showAlert(AlertType.SUCCESS, 'Template saved successfully!');
    }
    catch {
      this.recoverFromFail();
      ModalService.closeModal('save-template');
    }

    this.loading.action = false;
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

  // ----------------------------------------------------------------
  // Previews -------------------------------------------------------
  // ----------------------------------------------------------------

  async doAction(action: string): Promise<void>{
    if (action === 'Manage Versions') {
      this.manageAspects = true;
      ModalService.openModal('manage-versions');
    }
    else if (action === 'Undo') {
      if (this.history.hasUndo()) {
        this.loadFromHistory(this.history.undo());
      }
    }
    else if (action === 'Redo') {
      if (this.history.hasRedo()) {
        this.loadFromHistory(this.history.redo());
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
      if (this.page) {
        this.previewMode = 'mock';
        if (this.history.hasUndo()) {
          AlertService.clear(AlertType.ERROR);
          ErrorService.clearView();
          ModalService.openModal('save-before-preview');
        } else {
          await this.previewWithMockData();
        }
      }
      else if (this.pageToManage) {
        this.previewMode = 'mock';
        ModalService.openModal('save-new-before-preview');
      }
    }
    else if (action === 'Final preview (real data)') {
      if (this.page) {
        this.previewMode = 'real';
        if (this.history.hasUndo()) {
          AlertService.clear(AlertType.ERROR);
          ErrorService.clearView();
          ModalService.openModal('save-before-preview');
        }
        else {
          await this.selectUsersToPreview();
        }
      }
      else if (this.pageToManage) {
        this.previewMode = 'real';
        AlertService.clear(AlertType.ERROR);
        ErrorService.clearView();
        ModalService.openModal('save-new-before-preview');
      }
    }
  }

  async saveBeforePreview() {
    if (this.page) {
      this.loading.action = true;

      const res = await this.saveChanges();

      if (res === "error") {
        this.previewMode = "raw";
        ModalService.closeModal('save-before-preview');
        return;
      }

      if (this.previewMode === 'mock') {
        await this.previewWithMockData();
        ModalService.closeModal('save-before-preview');
      }
      else if (this.previewMode === 'real') {
        ModalService.closeModal('save-before-preview');
        await this.selectUsersToPreview();
      }
    }

    else if (this.pageToManage) {
      this.loading.action = true;

      const res = await this.savePage();

      if (res === "error") {
        this.previewMode = "raw";
        ModalService.closeModal('save-new-before-preview');
        return;
      }

      if (this.previewMode === 'mock') {
        await this.previewWithMockData();
      } else if (this.previewMode === 'real') {
        await this.selectUsersToPreview();
      }
    }
  }

  cancelPreview() {
    this.previewMode = "raw";
    this.loading.users = true;
  }

  async selectUsersToPreview() {
    await this.getViewersToPreview();
    await this.getUsersToPreview();
    this.loading.users = false;

    if (!this.viewerToPreview)
      this.viewerToPreview = this.viewersToPreview.find(e => e.value == this.user.id)?.value;

    if (!this.userToPreview)
      this.userToPreview = this.usersToPreview.find(e => e.value == this.user.id)?.value;

    AlertService.clear(AlertType.ERROR);
    ErrorService.clearView();
    ModalService.openModal('preview-as');
  }

  async getViewersToPreview() {
    let res;
    if (this.service.selectedAspect.viewerRole) {
      res = await this.api.getCourseUsersWithRole(this.course.id, this.service.selectedAspect.viewerRole, true).toPromise();
    } else {
      res = await this.api.getCourseUsers(this.course.id, true).toPromise();
    }
    this.viewersToPreview = res.map(e => { return { value: e.id, text: e.name } });
  }

  async getUsersToPreview() {
    let res;
    if (this.service.selectedAspect.userRole) {
      res = await this.api.getCourseUsersWithRole(this.course.id, this.service.selectedAspect.userRole, true).toPromise();
    } else {
      res = await this.api.getCourseUsers(this.course.id, true).toPromise();
    }
    this.usersToPreview = res.map(e => { return { value: e.id, text: e.name } });
  }

  async previewWithRealData() {
    if (!this.viewerToPreview || !this.userToPreview) {
      AlertService.showAlert(AlertType.ERROR, "Both user fields are required.");
      return;
    }

    this.loading.action = true;
    AlertService.clear(AlertType.ERROR);
    try {
      this.view = await this.api.previewPage(this.page.id, this.viewerToPreview, this.userToPreview).toPromise();
    }
    catch (err) {
      if (!(err instanceof HttpErrorResponse)) AlertService.showAlert(AlertType.ERROR, err);
      this.previewMode = "raw";
    }
    this.loading.action = false;
    ModalService.closeModal('preview-as');
  }

  async previewWithMockData() {
    AlertService.clear(AlertType.ERROR);
    ErrorService.clearView();

    this.loading.action = true;
    try {
      this.view = await this.api.renderPageWithMockData(this.page.id, this.service.selectedAspect).toPromise();
    }
    catch (err) {
      if (!(err instanceof HttpErrorResponse)) AlertService.showAlert(AlertType.ERROR, err);
      this.previewMode = "raw";
    }
    this.loading.action = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  loadFromHistory(data: HistoryEntry) {
    this.service.viewsByAspect = data.viewsByAspect;
    setGroupedChildren(data.groupedChildren);
    setViewsDeleted(data.viewsDeleted);

    this.view = this.service.getSelectedView();
  }

  recoverFromFail() {
    this.loadFromHistory(this.history.getMostRecent());
  }

  get ViewType(): typeof ViewType {
    return ViewType;
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

  getSubcategories(): { category: string, views: View[] }[] {
    return this.getSelectedCategories()[0]['list'];
  }

  openAddTemplateModal(item: any) {
    this.templateToAdd = item;
    ModalService.openModal('add-template');
  }

  discardAddTemplate() {
    this.optionSelected = null;
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

  openTutorial(){
    ModalService.openModal('views-tutorial');
  }

  closeTutorial(){
    ModalService.closeModal('views-tutorial');
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
  else if (type === ViewType.COLLAPSE) return [ViewCollapse.toDatabase(view as ViewCollapse, true)];
  else if (type === ViewType.ICON) return [ViewIcon.toDatabase(view as ViewIcon)];
  else if (type === ViewType.IMAGE) return [ViewImage.toDatabase(view as ViewImage)];
  else if (type === ViewType.ROW) return [ViewRow.toDatabase(view as ViewRow, true)];
  else if (type === ViewType.TABLE) return [ViewTable.toDatabase(view as ViewTable, true)];
  else if (type === ViewType.TEXT) return [ViewText.toDatabase(view as ViewText)];
  // NOTE: insert here other types of building-blocks

  return null;
}
