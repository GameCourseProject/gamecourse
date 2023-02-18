import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {NgForm} from "@angular/forms";
import {Course} from "../../../../../../../_domain/courses/course";
import {User} from "../../../../../../../_domain/users/user";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {ActivatedRoute} from "@angular/router";
import {ModalService} from "../../../../../../../_services/modal.service";
import {GameElement} from "../../../../../../../_domain/adaptation/GameElement";

@Component({
  selector: 'app-preference-questionnaire',
  templateUrl: './preference-questionnaire.component.html'
})
export class PreferenceQuestionnaireComponent implements OnInit{

  @Input() course: Course;
  @Input() user: User;
  @Input() gameElement: GameElement;

  @Input() questionnaires: QuestionnaireManageData[];

  @Output() questionnairesAfterSubmit = new EventEmitter<QuestionnaireManageData[]>();
  @Output() isQuestionnaire: boolean;

  @ViewChild('q', {static: false}) q: NgForm;       // questionnaire form (non-admin)

  loading = { action:false };
  mode: 'questionnaire';

  questionnaireToManage: QuestionnaireManageData = this.initQuestionnaireToManage();

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe();
    this.questionnaireToManage = this.initQuestionnaireToManage();
    //this.questionnaireToManage.element = this.gameElement.module;
  }

  async submitQuestionnaire(){
    if ((this.questionnaireToManage.q1 === true && this.questionnaireToManage.q2 && this.questionnaireToManage.q3) ||
      this.questionnaireToManage.q1 === false)
    {
      this.questionnaireToManage.element = this.gameElement.module;
      await this.api.submitGameElementQuestionnaire(clearEmptyValues(this.questionnaireToManage)).toPromise();

      const index = this.questionnaires.findIndex(q => q.element === this.questionnaireToManage.element);

      // NOTE: q1, q2, q3 are not updated, but we don't need them
      this.questionnaires[index].isAnswered = true;
      this.questionnairesAfterSubmit.emit(this.questionnaires);

      ModalService.closeModal('questionnaire');
      AlertService.showAlert(AlertType.SUCCESS, 'Questionnaire Submitted');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  initQuestionnaireToManage(): QuestionnaireManageData{
    return {
      course: 1,
      user: 1,
      q1: null,
      q2: null,
      q3: null,
      element: "",
      isAnswered: false
    };
  }

  resetQuestionnaireManage(){
    this.questionnaireToManage = this.initQuestionnaireToManage();
    this.mode = null;
    this.q.resetForm();
  }

}

export interface QuestionnaireManageData{
  course?: number,
  user?: number,
  q1?: boolean,
  q2?: string,
  q3?: number,
  element?: string,
  isAnswered?: boolean
}
