import {Component, OnInit, ViewChild} from '@angular/core';
import {CourseManageData} from "../../../courses/courses.component";
import {Course} from "../../../../../../_domain/courses/course";
import {ActivatedRoute} from "@angular/router";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {Theme} from "../../../../../../_services/theming/themes-available";
import {ThemingService} from "../../../../../../_services/theming/theming.service";

@Component({
  selector: 'app-settings',
  templateUrl: './settings.component.html'
})
export class SettingsComponent implements OnInit {

  loading = {
    page: true,
    table: true,
    action: false
  }

  course: Course;
  courseToManage: CourseManageData = this.initCourseToManage();
  editYearOptions: {value: string, text: string}[] = this.initYearOptions();
  defaultTheme: Theme;

  @ViewChild('f', { static: false }) f: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private themeService: ThemingService
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      this.courseToManage = this.initCourseToManage(this.course);
      this.defaultTheme = await this.themeService.getUserPreference();
      this.loading.page = false;
    });
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async editCourse(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      const courseEdited = await this.api.editCourse(clearEmptyValues(this.courseToManage)).toPromise();

      this.loading.action = false;
      AlertService.showAlert(AlertType.SUCCESS, 'Course \'' + courseEdited.name + '\' edited');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initCourseToManage(course?: Course): CourseManageData {
    const courseData: CourseManageData = {
      name: course?.name ?? null,
      short: course?.short ?? null,
      color: course?.color ?? null,
      year: course?.year ?? null,
      startDate: course?.startDate?.format('YYYY-MM-DD') ?? null,
      endDate: course?.endDate?.format('YYYY-MM-DD') ?? null,
      avatars: course?.avatars ?? false,
      theme: (course?.theme as Theme) ?? null
    };
    if (course) courseData.id = course.id;
    return courseData;
  }

  initYearOptions(): {value: string, text: string}[] {
    const years = [];
    const now = new Date();
    const currentYear = now.getFullYear();

    const YEARS_BEFORE = 1;
    const YEARS_AFTER = 5;

    let i = -YEARS_BEFORE;
    while (currentYear + i < currentYear + YEARS_AFTER) {
      const year = (currentYear + i) + '-' + (currentYear + i + 1);
      years.push({value: year, text: year});
      i++;
    }

    return years;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Themes ------------------ ***/
  /*** --------------------------------------------- ***/

  protected readonly Theme = Theme;

  getThemes() {
    return Object.values(Theme).filter(e => e != Theme.DARK && e != Theme.LIGHT);
  }

  async selectTheme(theme: string) {
    this.courseToManage.theme = (theme as Theme) ?? null;
    if (theme) {
      this.themeService.previewTheme(theme as Theme);
    } else {
      this.defaultTheme = await this.themeService.getUserPreference();
      this.themeService.previewTheme(this.defaultTheme);
    }
  }

}
