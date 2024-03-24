import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from "@angular/router";
import { FormsModule } from "@angular/forms";

// Pipes
import { AsPipe } from "./_pipes/as.pipe";
import { SanitizeHTMLPipe } from "./_pipes/sanitize-html.pipe";

// Directives
import { ClickedOutsideDirective } from "./_directives/clicked-outside.directive";
import { ViewSelectionDirective } from "./_directives/view-selection.directive";
import { ScrollableDragDirective } from "./_directives/scrollable-drag.directive";
import { GoToPageDirective } from "./_directives/views/events/actions/go-to-page.directive";
import { ShowTooltipDirective } from "./_directives/views/events/actions/show-tooltip.directive";
import { TableDataCustomDirective } from './_components/tables/table-data/table-data-custom.directive';
import { DropZoneDirective } from './_directives/dropzone.directive';

// Components: layout
import { NavbarComponent } from './_components/layout/navbar/navbar.component';
import { SidebarComponent } from './_components/layout/sidebar/sidebar.component';

// Components: modals
import { ModalComponent } from './_components/modals/modal/modal.component';
import { SimpleModalComponent } from './_components/modals/simple-modal/simple-modal.component';
import { ErrorModalComponent } from './_components/modals/error-modal/error-modal.component';
import { FilePickerModalComponent } from "./_components/modals/file-picker-modal/file-picker-modal.component";

// Components: inputs
import { InputTextComponent } from "./_components/inputs/general/input-text/input-text.component";
import { InputNumberComponent } from "./_components/inputs/general/input-number/input-number.component";
import { InputSearchComponent } from './_components/inputs/general/input-search/input-search.component';
import { InputUrlComponent } from './_components/inputs/general/input-url/input-url.component';
import { InputTextareaComponent } from './_components/inputs/general/input-textarea/input-textarea.component';
import { InputEmailComponent } from './_components/inputs/personal-info/input-email/input-email.component';
import { InputColorComponent } from './_components/inputs/color/input-color/input-color.component';
import { InputFileComponent } from './_components/inputs/general/input-file/input-file.component';
import { InputCodeComponent } from './_components/inputs/code/input-code/input-code.component';
import { InputRichTextComponent } from "./_components/inputs/rich-text/input-rich-text/input-rich-text.component";
import { InputNotificationTextComponent} from "./_components/inputs/rich-text/input-notification-text/input-notification-text.component";
import { InputCheckboxComponent } from './_components/inputs/checkbox & radio/input-checkbox/input-checkbox.component';
import { InputRadioComponent } from './_components/inputs/checkbox & radio/input-radio/input-radio.component';
import { InputToggleComponent } from './_components/inputs/toggle/input-toggle/input-toggle.component';
import { InputSelectComponent } from './_components/inputs/select/input-select/input-select.component';
import { InputSelectWeekdayComponent } from './_components/inputs/select/input-select-weekday/input-select-weekday.component';
import { InputSelectRoleComponent } from './_components/inputs/select/input-select-role/input-select-role.component';
import { InputDateComponent } from './_components/inputs/date & time/input-date/input-date.component';
import { InputTimeComponent } from './_components/inputs/date & time/input-time/input-time.component';
import { InputDatetimeComponent } from './_components/inputs/date & time/input-datetime/input-datetime.component';
import { InputPeriodicityComponent } from './_components/inputs/date & time/input-periodicity/input-periodicity.component';
import { InputScheduleComponent } from './_components/inputs/date & time/input-schedule/input-schedule.component';
import { ThemeTogglerComponent } from './_components/inputs/misc/theme-toggler/theme-toggler.component';

// Components: charts
import { BarChartComponent } from "./_components/charts/bar-chart/bar-chart.component";
import { ComboChartComponent } from './_components/charts/combo-chart/combo-chart.component';
import { LineChartComponent } from "./_components/charts/line-chart/line-chart.component";
import { ProgressChartComponent } from "./_components/charts/progress-chart/progress-chart.component";
import { RadarChartComponent } from "./_components/charts/radar-chart/radar-chart.component";
import { PieChartComponent } from "./_components/charts/pie-chart/pie-chart.component";
// Components: tables
import { TableComponent } from "./_components/tables/table/table.component";
import { TableData } from "./_components/tables/table-data/table-data.component";

// Components: cards
import { CourseCardComponent } from './_components/cards/course-card/course-card.component';
import { ModuleCardComponent } from './_components/cards/module-card/module-card.component';
import { AspectCardComponent } from './_components/cards/aspect-card/aspect-card.component';
import { AuxVarCardComponent } from './_components/cards/aux-var-card/aux-var-card.component';
import { EventCardComponent } from './_components/cards/event-card/event-card.component';
import { DatalabelCardComponent } from './_components/cards/datalabel-card/datalabel-card.component';

// Components: avatars
import { AvatarSvgComponent } from './_components/avatar-generator/avatar-svg/avatar-svg.component';
import { AvatarGeneratorComponent } from './_components/avatar-generator/avatar-generator.component';

// Components: alerts
import { AlertComponent } from './_components/alerts/alert/alert.component';

// Components: spinners
import { SpinnerComponent } from './_components/spinners/spinner/spinner.component';

// Components: skeletons
import { CourseSkeletonComponent } from './_components/skeletons/course-skeleton/course-skeleton.component';

// Components: helpers
import { ImportHelperComponent } from './_components/helpers/import-helper/import-helper.component';
import { SimpleHelperComponent } from './_components/helpers/simple-helper/simple-helper.component';

// Components: building blocks
import { BBAnyComponent } from "./_components/building-blocks/any/any.component";
import { BBBlockComponent } from './_components/building-blocks/block/block.component';
import { BBButtonComponent } from './_components/building-blocks/button/button.component';
import { BBChartComponent } from "./_components/building-blocks/chart/chart.component";
import { BBCollapseComponent } from './_components/building-blocks/collapse/collapse.component';
import { BBIconComponent } from './_components/building-blocks/icon/icon.component';
import { BBImageComponent } from './_components/building-blocks/image/image.component';
import { BBTableComponent } from './_components/building-blocks/table/table.component';
import { BBTextComponent } from './_components/building-blocks/text/text.component';
import { ComponentEditorComponent } from './_views/restricted/courses/course/settings/views/views-editor/component-editor/component-editor.component';

// Components: misc
import { AutoGameToastComponent } from './_components/misc/autogame-toast/auto-game-toast.component';
import { HeaderComponent } from './_components/misc/header/header.component';
import { TopActionsComponent } from './_components/misc/top-actions/top-actions.component';
import { LoaderComponent } from './_components/misc/loader/loader.component';
import { LogsViewerComponent } from './_components/misc/logs-viewer/logs-viewer.component';

import { PageNotFoundComponent } from './_components/misc/pages/page-not-found/page-not-found.component';
import { NoAccessComponent } from './_components/misc/pages/no-access/no-access.component';
import { ComingSoonComponent } from './_components/misc/pages/coming-soon/coming-soon.component';

// Libraries
import { NgIconsModule } from "@ng-icons/core";
import { DataTablesModule } from "angular-datatables";
import { NgApexchartsModule } from "ng-apexcharts";
import { ScrollingModule } from "@angular/cdk/scrolling";
import { MentionModule } from "angular-mentions";

// Icons
import {
  featherAlertTriangle,
  featherArrowRightCircle,
  featherCheckCircle,
  featherFile,
  featherHome,
  featherInfo,
  featherLayout,
  featherLogOut,
  featherMenu,
  featherMoon,
  featherMoreVertical,
  featherMove,
  featherPlayCircle,
  featherPlusCircle,
  featherRefreshCcw,
  featherRepeat,
  featherSearch,
  featherSliders,
  featherSun,
  featherType,
  featherUser,
  featherUserCheck,
  featherUserPlus,
  featherUsers,
  featherVolume2,
  featherX,
  featherXCircle
} from "@ng-icons/feather-icons";

import {
  jamCircleF,
  jamDownload,
  jamEyeF,
  jamGoogle,
  jamFacebook,
  jamFilesF,
  jamLayout,
  jamLayoutF,
  jamLinkedin,
  jamPadlockOpenF,
  jamPadlockF,
  jamPencil,
  jamPencilF,
  jamPlus,
  jamPlusCircle,
  jamPlusCircleF,
  jamStopSign,
  jamTrashF,
  jamUpload,
  jamBox,
  jamShuffle
} from "@ng-icons/jam-icons";

import {
  tablerAlertTriangle,
  tablerArchive,
  tablerArrowBackUp,
  tablerArrowForwardUp,
  tablerArrowNarrowRight,
  tablerArrowNarrowDown,
  tablerArrowNarrowLeft,
  tablerArrowNarrowUp,
  tablerArrowsUpDown,
  tablerArrowsVertical,
  tablerAward,
  tablerChartBar,
  tablerCheck,
  tablerBarrierBlock,
  tablerBell,
  tablerBellRinging,
  tablerBellSchool,
  tablerBook,
  tablerBooks,
  tablerBulb,
  tablerCalendarTime,
  tablerCaretDown,
  tablerChartPie,
  tablerChecks,
  tablerCircleCheck,
  tablerClipboardList,
  tablerCloudUpload,
  tablerCoin,
  tablerColorSwatch,
  tablerCopy,
  tablerDeviceDesktop,
  tablerEye,
  tablerEyeOff,
  tablerFlame,
  tablerFlask,
  tablerFolder,
  tablerGavel,
  tablerGift,
  tablerIdBadge2,
  tablerListNumbers,
  tablerMovie,
  tablerMessage2,
  tablerPaperclip,
  tablerPlug,
  tablerPresentation,
  tablerPrompt,
  tablerPuzzle,
  tablerQuestionMark,
  tablerSelector,
  tablerSettings,
  tablerStar,
  tablerSchool,
  tablerNewSection,
  tablerTags,
  tablerTimeline,
  tablerTrophy,
  tablerUserCircle,
  tablerUsers,
  tablerDotsVertical,
  tablerBrush,
  tablerRowInsertBottom,
  tablerRowInsertTop,
  tablerColumnInsertLeft,
  tablerColumnInsertRight,
  tablerQuote,
  tablerDatabase
} from "@ng-icons/tabler-icons";

import {
  matFaceRetouchingNatural
} from "@ng-icons/material-icons/baseline"

import {
  heroFireSolid
} from "@ng-icons/heroicons/solid"

@NgModule({
  declarations: [
    AsPipe,
    SanitizeHTMLPipe,

    ClickedOutsideDirective,
    ViewSelectionDirective,
    ScrollableDragDirective,
    GoToPageDirective,
    ShowTooltipDirective,
    TableDataCustomDirective,
    DropZoneDirective,

    NavbarComponent,
    SidebarComponent,

    ModalComponent,
    SimpleModalComponent,
    ErrorModalComponent,
    FilePickerModalComponent,

    InputTextComponent,
    InputNumberComponent,
    InputSearchComponent,
    InputUrlComponent,
    InputTextareaComponent,
    InputEmailComponent,
    InputColorComponent,
    InputFileComponent,
    InputCodeComponent,
    InputRichTextComponent,
    InputNotificationTextComponent,
    InputCheckboxComponent,
    InputRadioComponent,
    InputToggleComponent,
    InputSelectComponent,
    InputSelectWeekdayComponent,
    InputSelectRoleComponent,
    InputDateComponent,
    InputTimeComponent,
    InputDatetimeComponent,
    InputPeriodicityComponent,
    InputScheduleComponent,
    ThemeTogglerComponent,

    BarChartComponent,
    ComboChartComponent,
    LineChartComponent,
    ProgressChartComponent,
    RadarChartComponent,
    PieChartComponent,

    TableComponent,
    TableData,

    CourseCardComponent,
    ModuleCardComponent,
    AspectCardComponent,
    AuxVarCardComponent,
    EventCardComponent,
    DatalabelCardComponent,

    AvatarSvgComponent,
    AvatarGeneratorComponent,

    AlertComponent,

    SpinnerComponent,

    CourseSkeletonComponent,

    ImportHelperComponent,
    SimpleHelperComponent,

    BBAnyComponent,
    BBBlockComponent,
    BBButtonComponent,
    BBChartComponent,
    BBCollapseComponent,
    BBIconComponent,
    BBImageComponent,
    BBTableComponent,
    BBTextComponent,
    ComponentEditorComponent,

    AutoGameToastComponent,
    HeaderComponent,
    TopActionsComponent,
    LoaderComponent,
    LogsViewerComponent,
    PageNotFoundComponent,
    NoAccessComponent,
    ComingSoonComponent
  ],
  exports: [
    AsPipe,
    SanitizeHTMLPipe,

    ClickedOutsideDirective,
    ViewSelectionDirective,
    GoToPageDirective,
    ShowTooltipDirective,
    TableDataCustomDirective,

    NavbarComponent,
    SidebarComponent,

    ModalComponent,
    SimpleModalComponent,
    ErrorModalComponent,
    FilePickerModalComponent,

    InputTextComponent,
    InputNumberComponent,
    InputSearchComponent,
    InputUrlComponent,
    InputTextareaComponent,
    InputEmailComponent,
    InputColorComponent,
    InputFileComponent,
    InputCodeComponent,
    InputRichTextComponent,
    InputNotificationTextComponent,
    InputCheckboxComponent,
    InputRadioComponent,
    InputToggleComponent,
    InputSelectComponent,
    InputSelectWeekdayComponent,
    InputSelectRoleComponent,
    InputDateComponent,
    InputTimeComponent,
    InputDatetimeComponent,
    InputPeriodicityComponent,
    InputScheduleComponent,
    ThemeTogglerComponent,

    BarChartComponent,
    ComboChartComponent,
    LineChartComponent,
    ProgressChartComponent,
    RadarChartComponent,
    PieChartComponent,

    TableComponent,
    TableData,

    CourseCardComponent,
    ModuleCardComponent,
    AspectCardComponent,
    AuxVarCardComponent,
    EventCardComponent,
    DatalabelCardComponent,

    AvatarSvgComponent,
    AvatarGeneratorComponent,

    AlertComponent,

    SpinnerComponent,

    CourseSkeletonComponent,

    ImportHelperComponent,
    SimpleHelperComponent,

    BBAnyComponent,
    BBBlockComponent,
    BBButtonComponent,
    BBChartComponent,
    BBIconComponent,
    BBImageComponent,
    BBTableComponent,
    BBTextComponent,

    AutoGameToastComponent,
    HeaderComponent,
    TopActionsComponent,
    LoaderComponent,
    LogsViewerComponent,
    PageNotFoundComponent,
    NoAccessComponent,
    ComingSoonComponent,

    NgIconsModule,
    FormsModule,
    ScrollableDragDirective
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,

    NgIconsModule.withIcons({
      featherAlertTriangle,
      featherArrowRightCircle,
      featherCheckCircle,
      featherFile,
      featherHome,
      featherInfo,
      featherLayout,
      featherLogOut,
      featherMenu,
      featherMoon,
      featherMoreVertical,
      featherMove,
      featherPlayCircle,
      featherPlusCircle,
      featherRefreshCcw,
      featherRepeat,
      featherSearch,
      featherSliders,
      featherSun,
      featherType,
      featherUser,
      featherUserCheck,
      featherUserPlus,
      featherUsers,
      featherVolume2,
      featherX,
      featherXCircle,

      jamCircleF,
      jamDownload,
      jamEyeF,
      jamGoogle,
      jamFacebook,
      jamFilesF,
      jamLayout,
      jamLayoutF,
      jamLinkedin,
      jamPadlockOpenF,
      jamPadlockF,
      jamPencil,
      jamPencilF,
      jamPlus,
      jamPlusCircle,
      jamPlusCircleF,
      jamStopSign,
      jamTrashF,
      jamUpload,
      jamBox,
      jamShuffle,

      tablerAlertTriangle,
      tablerArchive,
      tablerArrowBackUp,
      tablerArrowForwardUp,
      tablerArrowNarrowRight,
      tablerArrowNarrowDown,
      tablerArrowNarrowUp,
      tablerArrowNarrowLeft,
      tablerArrowsUpDown,
      tablerArrowsVertical,
      tablerAward,
      tablerChartBar,
      tablerCheck,
      tablerBarrierBlock,
      tablerBell,
      tablerBellRinging,
      tablerBellSchool,
      tablerBook,
      tablerBooks,
      tablerBulb,
      tablerCalendarTime,
      tablerCaretDown,
      tablerChartPie,
      tablerChecks,
      tablerCircleCheck,
      tablerClipboardList,
      tablerCloudUpload,
      tablerCoin,
      tablerColorSwatch,
      tablerDeviceDesktop,
      tablerCopy,
      tablerEye,
      tablerEyeOff,
      tablerFolder,
      tablerFlame,
      tablerFlask,
      tablerGavel,
      tablerGift,
      tablerIdBadge2,
      tablerListNumbers,
      tablerNewSection,
      tablerMessage2,
      tablerMovie,
      tablerPaperclip,
      tablerPresentation,
      tablerPlug,
      tablerPrompt,
      tablerPuzzle,
      tablerSchool,
      tablerQuestionMark,
      tablerSelector,
      tablerSettings,
      tablerStar,
      tablerTags,
      tablerTimeline,
      tablerTrophy,
      tablerUserCircle,
      tablerUsers,
      tablerDotsVertical,
      tablerBrush,
      tablerRowInsertBottom,
      tablerRowInsertTop,
      tablerColumnInsertLeft,
      tablerColumnInsertRight,
      tablerQuote,
      tablerDatabase,

      matFaceRetouchingNatural,

      heroFireSolid
    }),
    DataTablesModule,
    NgApexchartsModule,
    ScrollingModule,
    MentionModule
  ]
})
export class SharedModule { }
