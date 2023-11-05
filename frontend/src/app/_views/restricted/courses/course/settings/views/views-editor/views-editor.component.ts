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
import { ViewButton, ViewButtonDatabase } from "src/app/_domain/views/view-types/view-button";
import { ViewChart, ViewChartDatabase } from "src/app/_domain/views/view-types/view-chart";
import { ViewCollapse, ViewCollapseDatabase } from "src/app/_domain/views/view-types/view-collapse";
import { ViewIcon, ViewIconDatabase } from "src/app/_domain/views/view-types/view-icon";
import { ViewImage, ViewImageDatabase } from "src/app/_domain/views/view-types/view-image";
import { ViewRow, ViewRowDatabase } from "src/app/_domain/views/view-types/view-row";
import { ViewTable, ViewTableDatabase } from "src/app/_domain/views/view-types/view-table";
import { ViewText, ViewTextDatabase } from "src/app/_domain/views/view-types/view-text";
  
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
    action: false
  };

  course: Course;                 // Specific course in which page exists
  page: Page;                     // page where information will be saved
  pageToManage: PageManageData;   // Manage data

  previewMode: 'raw' | 'mock' | 'real' = 'raw';   // Preview mode selected to render

  options: Option[];
  activeSubMenu: SubMenu;

  view: View;

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
      this.setOptions();

      this.route.params.subscribe(async childParams => {
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;

        if (segment === 'new-page') {
          // Prepare for creation
          this.pageToManage = initPageToManage(courseID);
        } else {
          await this.getPage(parseInt(segment));
          await this.getView();
          this.view.switchMode(ViewMode.EDIT);
        }

      });
      this.loading.page = false;
    })
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getPage(pageID: number): Promise<void> {
    this.page = await this.api.getPageById(pageID).toPromise();
  }

  async getView(): Promise<void> {
    this.view = await this.api.renderPageInEditor(this.page.id).toPromise();
  }

  async setOptions() {
    const custom = await this.api.getCustomComponents(this.course.id).toPromise();
    const core = await this.api.getCoreComponents().toPromise();

    // Build views
    const types = Object.keys(core);
    for (let type of types) {
      const categories = Object.keys(core[type]);
      for (let category of categories) {
        core[type][category] = core[type][category].map((e) => {
          const view = buildView(e);
          view.switchMode(ViewMode.PREVIEW);
          return view;
        })
      }
    }
  
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
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.BLOCK]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.BLOCK)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.BUTTON,
              isSelected: false,
              helper: 'Component that displays different types of buttons.',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.BUTTON]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.BUTTON)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.CHART,
              isSelected: false,
              helper: 'Component composed by other components.',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.CHART]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.CHART)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.COLLAPSE,
              isSelected: false,
              helper: 'Component that can hide or show other components.',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.COLLAPSE]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.COLLAPSE)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.ICON,
              isSelected: false,
              helper: 'Displays an icon.',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.ICON]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.ICON)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.IMAGE,
              isSelected: false,
              helper: 'Displays either simple static visual components or more complex ones built using expressions',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.IMAGE]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.IMAGE)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.TABLE,
              isSelected: false,
              helper: 'Displays a table with columns and rows. Can display a variable number of headers as well.',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.TABLE]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.TABLE)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
            { title: ViewType.TEXT,
              isSelected: false,
              helper: 'Displays either simle static written components or more complex ones built using expressions.',
              items: [
                { type: 'System',
                  isSelected: false,
                  helper: TypeHelper.SYSTEM,
                  list: core[ViewType.TEXT]
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: custom.filter((view) => view.type == ViewType.TEXT)
                },
                {
                  type: 'Shared',
                  isSelected: false,
                  helper: TypeHelper.SHARED,
                  list: [] // FIXME: should get them from backend
                }
              ]
            },
          ]
      }},
      { icon: 'jam-layout',
        iconSelected: 'jam-layout-f',
        isSelected: false,
        description: 'Choose Template',
        subMenu: {
          title: 'Templates',
          helper: 'Templates are final drafts of pages that have not been published yet. Its a layout of what a future page will look like.',
          items: []
        }
      },
      {
        icon: 'feather-move',
        iconSelected: 'feather-move',
        isSelected: false,
        description: 'Rearrange'
      }
    ];
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

  async closeEditor(){
    await this.router.navigate(['pages'], {relativeTo: this.route.parent});
  }

  addToPage(item: View) {
    let itemToAdd = _.cloneDeep(item);
    itemToAdd.mode = ViewMode.EDIT;

    // Add child to the selected block
    if (this.selection.get()?.type == ViewType.BLOCK) { // FIXME: will be possible to add to collapse, etc.
      this.selection.get().addChildViewToViewTree(itemToAdd);
    }
    // No valid selection, view is empty, and not adding block
    else if (!this.view && itemToAdd.type != ViewType.BLOCK) {
      this.view = buildView(
        {
          id: 1,
          viewRoot: null,
          aspect: {viewerRole: null, userRole: null},
          type: "block",
          class: "card bg-base-100 shadow-xl",
        }
      )
      this.view.addChildViewToViewTree(itemToAdd);
      this.view.mode = ViewMode.EDIT;
    }
    // Adding first block
    else if (!this.view && itemToAdd.type == ViewType.BLOCK) {
      this.view = itemToAdd;
    }
    // By default without valid selection add to existing root
    else {
      this.view.addChildViewToViewTree(itemToAdd);
    }

    this.resetMenus();
  }

  async savePage() {
    await this.api.saveViewAsPage(this.course.id, this.pageToManage.name, buildViewTree(this.view)).toPromise();
    await this.router.navigate(['pages'], { relativeTo: this.route.parent });
    AlertService.showAlert(AlertType.SUCCESS, 'Page Created');
  }

  async doActionPreview(action: string): Promise<void>{
    if (action === 'Raw (default)') {
      this.previewMode = 'raw';
      this.view = await this.api.renderPageInEditor(this.page.id).toPromise();
      this.view.switchMode(ViewMode.EDIT);
    }
    else if (action === 'Final preview (real data)') {
      this.previewMode = 'real';
      this.view = await this.api.previewPage(this.page.id, this.view.aspect).toPromise();
    }
    else if (action === 'Layout preview (mock data)') {
      this.previewMode = 'mock';
      this.view = await this.api.renderPageWithMockData(this.page.id).toPromise();
    }
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
    for (let i = 0; i < this.options.length; i++){
      this.options[i].isSelected = false;

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
    if (this.getSelectedCategories()[0]['list'])
      return Object.keys(this.getSelectedCategories()[0]['list']);
    else return [];
  }

  getComponentsOfSubcategory(subcategory: string) {
    return this.getSelectedCategories()[0]['list'][subcategory];
  }

  openSaveAsPageModal() {
    ModalService.openModal('save-page');
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

export function buildViewTree(view: View): ViewBlockDatabase[] | ViewButtonDatabase[] | ViewChartDatabase[]
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