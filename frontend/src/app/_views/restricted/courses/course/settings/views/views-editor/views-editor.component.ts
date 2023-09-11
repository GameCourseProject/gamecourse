import {Component, OnInit} from "@angular/core";
import {ActivatedRoute, Router} from "@angular/router";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {Page} from "src/app/_domain/views/pages/page";

import {initPageToManage, PageManageData} from "../views/views.component";
import {ViewType} from "src/app/_domain/views/view-types/view-type";

@Component({
  selector: 'app-views-editor',
  templateUrl: './views-editor.component.html'
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

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
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

  setOptions(){
    // FIXME: move to backend maybe ?
    this.options =  [
      { icon: 'jam-plus-circle',
        iconSelected: 'jam-plus-circle-f',
        isSelected: false,
        description: 'Add component',
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
                  list: [] // FIXME: should get them from backend
                },
                { type: 'Custom',
                  isSelected: false,
                  helper: TypeHelper.CUSTOM,
                  list: [] // FIXME: should get them from backend
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
      { icon: 'jam-grid',
        iconSelected: 'jam-grid-f',
        isSelected: false,
        description: 'Add Section',
        subMenu: {
          title: 'Sections',
          helper: 'Small pages parts that are already configured and come from modules.',
          items: []
      }},
      { icon: 'jam-layout',
        iconSelected: 'jam-layout-f',
        isSelected: false,
        description: 'Choose Layout',
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
    console.log("ANTES: ", this.options);
    // if there's another option already selected
    let flag = false;
    for (let i = 0; i < this.options.length; i++) {
      if (this.options[i] === option){
        flag = true;
        continue;
      }
      this.options[i].isSelected = false;
    }

    if (flag) this.resetMenus();
    option.isSelected = !option.isSelected;

    // No menus active
    if (!option.isSelected) this.resetMenus();
    console.log("DPS: ", this.options);
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

      for (let j = 0; j < this.options[i].subMenu?.items.length; i++){
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
  type?: 'System' | 'Custom' | 'Shared',
  isSelected: boolean,
  helper?: TypeHelper,
  list: any | { category: string, items: any[] }
}

export enum TypeHelper {
  SYSTEM = 'System components are provided by GameCourse and already configured and ready for use.',
  CUSTOM = 'Custom components are created by users in this course.',
  SHARED = 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses.'
}
