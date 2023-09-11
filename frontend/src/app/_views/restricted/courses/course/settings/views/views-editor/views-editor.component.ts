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
      { icon: 'jam-plus-circle', iconSelected: 'jam-plus-circle-f',
        isSelected: false, description: 'Add component',
        subMenu: {
          title: 'Components',
          items: [
            { title: ViewType.BLOCK,
              helper: 'Component composed by other components.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.BUTTON,
              helper: 'Component that displays different types of buttons.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.CHART,
              helper: 'Component composed by other components.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.COLLAPSE,
              helper: 'Component that can hide or show other components.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.ICON,
              helper: 'Displays an icon.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.IMAGE,
              helper: 'Displays either simple static visual components or more complex ones built using expressions',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.TABLE,
              helper: 'Displays a table with columns and rows. Can display a variable number of headers as well.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
            { title: ViewType.TEXT,
              helper: 'Displays either simle static written components or more complex ones built using expressions.',
              items: {
                system: {
                  title : 'System',
                  helper: 'System components are provided by GameCourse and already configured and ready for use.',
                  items: [] // FIXME: should get them from backend
                },
                custom: {
                  title: 'Custom',
                  helper: 'Custom components are created by users in this course.',
                  items: [] // FIXME: should get them from backend
                },
                shared: {
                  title: 'Shared',
                  helper: 'Shared components are created by users in this course and shared with the rest of GameCourse\'s courses',
                  items: [] // FIXME: should get them from backend
                }
              }
            },
          ]
      }},
      { icon: 'jam-grid', iconSelected: 'jam-grid-f', isSelected: false,
        description: 'Add Section',
        subMenu: {
          title: 'Sections',
          helper: 'Small pages parts that are already configured and come from modules.',
          items: []
      }},
      { icon: 'jam-layout', iconSelected: 'jam-layout-f', isSelected: false, description: 'Choose Layout',
        subMenu: {
          title: 'Templates',
          helper: 'Templates are final drafts of pages that have not been published yet. Its a layout of what a future page will look like.',
          items: [] }},
      { icon: 'feather-move', iconSelected: 'feather-move', isSelected: false, description: 'Rearrange',
        subMenu: {title: 'Rearrange' }}
    ];
  }

  /*** ------------------------------------------------ ***/
  /*** -------------------- Actions ------------------- ***/
  /*** ------------------------------------------------ ***/

  selectOption(option: Option) {
    // if there's another option already selected
    let index = this.options.findIndex(op => op.isSelected && op !== option);
    if (index !== -1) this.options[index].isSelected = !this.options[index].isSelected;

    option.isSelected = !option.isSelected;
  }

  openSubMenu(option: Option, index: number) {
    console.log(option.subMenu.items[index].items);
  }

  async closeEditor(){
    await this.router.navigate(['pages'], {relativeTo: this.route.parent});
  }



  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getCourseColor(): string {
    return this.course.color;
  }

  getIcon(mode: string): string {
    if (mode === this.previewMode) return 'tabler-check';
    else return '';
  }

  getItems(option: Option){
    return Object.keys(option.subMenu.items);
  }

}

export interface Option {
  icon: string,
  iconSelected: string,
  isSelected: boolean,
  description: string,
  subMenu: subMenu
}

export interface subMenu {
  title: string,
  helper?: string,
  items?: subMenu[] | { system: subMenu, custom: subMenu, shared: subMenu } | null
}
