import {TestBed} from "@angular/core/testing";
import {HttpClientTestingModule, HttpTestingController} from "@angular/common/http/testing";
import {ApiHttpService} from "./api-http.service";
import {ApiEndpointsService} from "./api-endpoints.service";
import {dateFromDatabase} from "../../_utils/misc/misc";
import {Course} from "../../_domain/courses/course";

describe('API Service', () => {
  let service: ApiHttpService;
  let httpMock: HttpTestingController;

  const COURSE_ID = 1;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
    });
    service = TestBed.inject(ApiHttpService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });


  // TODO: finish writing tests on all api endpoints

  describe('Course', () => {
    const URL_BEGIN = ApiEndpointsService.API_ENDPOINT + '/info.php?module=course';

    it('should get a course', () => {
      const url = URL_BEGIN + '&request=getCourse&courseId=' + COURSE_ID;
      const response = {
        "data": {
          "course": {
            "id": COURSE_ID.toString(),
            "name": "Produção de Conteúdos Multimédia",
            "short": "PCM",
            "color": "#2167D0",
            "year": "2021-2022",
            "defaultLandingPage": "",
            "lastUpdate": "2021-10-22 17:50:16",
            "isActive": "1",
            "isVisible": "1",
            "roleHierarchy": "[{\"name\":\"Teacher\"},{\"name\":\"Student\"},{\"name\":\"Watcher\"}]",
            "theme": null
          }
        }
      };

      service.getCourse(COURSE_ID).subscribe(res => {
        expect(res).toBeTruthy();
        expect(res.id).toBe(COURSE_ID);
        expect(res.name).toBe("Produção de Conteúdos Multimédia");
        expect(res.short).toBe("PCM");
        expect(res.color).toBe("#2167D0");
        expect(res.year).toBe("2021-2022");
        expect(res.defaultLandingPage).toBe('');
        expect(res.lastUpdate).toEqual(dateFromDatabase("2021-10-22 17:50:16"));
        expect(res.isActive).toBeTrue();
        expect(res.isVisible).toBeTrue();
        expect(res.roleHierarchy).toBe("[{\"name\":\"Teacher\"},{\"name\":\"Student\"},{\"name\":\"Watcher\"}]");
        expect(res.theme).toBe(null);
        expect(res.nrStudents).toBe(undefined);
      })

      runTest(url, "GET", response);
    });

    it('should get a course with info', () => {
      const url = URL_BEGIN + '&request=getCourseWithInfo&courseId=' + COURSE_ID;
      const response = {
        "data": {
          "course": {
            "id": COURSE_ID.toString(),
            "name": "Produção de Conteúdos Multimédia",
            "short": "PCM",
            "color": "#2167D0",
            "year": "2021-2022",
            "defaultLandingPage": "",
            "lastUpdate": "2021-10-22 17:50:16",
            "isActive": "1",
            "isVisible": "1",
            "roleHierarchy": "[{\"name\":\"Teacher\"},{\"name\":\"Student\"},{\"name\":\"Watcher\"}]",
            "theme": null
          },
          "activePages": [
            {
              "id": "1",
              "course": "2",
              "name": "A Page",
              "theme": null,
              "viewId": "2732",
              "isEnabled": "1",
              "seqId": "1"
            },
            {
              "id": "2",
              "course": "2",
              "name": "Another Page",
              "theme": null,
              "viewId": "2732",
              "isEnabled": "1",
              "seqId": "2"
            }
          ]
        }
      };

      service.getCourseWithInfo(COURSE_ID).subscribe(res => {
        expect(res).toBeTruthy();
        expect(res.course).toBeTruthy();
        expect(res.activePages).toBeTruthy();
        expect(res.activePages.length).toBe(2);
        res.activePages.forEach(page => {
          expect(page).toBeTruthy();
        });
      })

      runTest(url, "GET", response);
    });
  });


  describe('Rules System', () => {
    const URL_BEGIN = ApiEndpointsService.API_ENDPOINT + '/info.php?module=course';

    it('should get date of when rules system was last run', () => {
      const url = URL_BEGIN + '&request=getRulesSystemLastRun&courseId=' + COURSE_ID;
      const response = {
        "data": {
          "ruleSystemLastRun": "2021-10-23 17:22:16"
        }
      };

      service.getRulesSystemLastRun(COURSE_ID).subscribe(res => {
        expect(res).toEqual(dateFromDatabase("2021-10-23 17:22:16"));
      })

      runTest(url, "GET", response);
    });
  })


  function runTest(url: string, method: 'GET' | 'POST', response: any) {
    const req = httpMock.expectOne(url);
    expect(req.request.method).toBe(method);
    req.flush(response);
  }

});
