import {View, ViewDatabase} from "../view";
import {ViewType} from "../view-types/view-type";
import {ViewBlock, ViewBlockDatabase} from "../view-types/view-block";
import {ViewButton, ViewButtonDatabase} from "../view-types/view-button";
import {ViewChart, ViewChartDatabase} from "../view-types/view-chart";
import {ViewCollapse, ViewCollapseDatabase} from "../view-types/view-collapse";
import {ViewIcon, ViewIconDatabase} from "../view-types/view-icon";
import {ViewImage, ViewImageDatabase} from "../view-types/view-image";
import {ViewRow, ViewRowDatabase} from "../view-types/view-row";
import {ViewTable, ViewTableDatabase} from "../view-types/view-table";
import {ViewText, ViewTextDatabase} from "../view-types/view-text";

/**
 * Builds a view from an object coming from the database
 * according to whatever type the view is.
 *
 * This function needs to be outside view.ts so as not to create a
 * Circular Dependency between View and its children.
 * @link https://stackoverflow.com/questions/49727530/how-to-move-typescript-classes-with-circular-dependency-into-separate-files
 * @class View
 *
 * @param obj
 */
export function buildView(obj: ViewBlockDatabase | ViewButtonDatabase | ViewChartDatabase | ViewCollapseDatabase |
    ViewIconDatabase | ViewImageDatabase | ViewRowDatabase | ViewTableDatabase | ViewTextDatabase, edit: boolean = false): View {
  const type = obj.type;

  if (type === ViewType.BLOCK) return ViewBlock.fromDatabase(obj as ViewBlockDatabase, edit);
  else if (type === ViewType.BUTTON) return ViewButton.fromDatabase(obj as ViewButtonDatabase);
  else if (type === ViewType.CHART) return ViewChart.fromDatabase(obj as ViewChartDatabase);
  else if (type === ViewType.COLLAPSE) return ViewCollapse.fromDatabase(obj as ViewCollapseDatabase, edit);
  else if (type === ViewType.ICON) return ViewIcon.fromDatabase(obj as ViewIconDatabase);
  else if (type === ViewType.IMAGE) return ViewImage.fromDatabase(obj as ViewImageDatabase);
  else if (type === ViewType.ROW) return ViewRow.fromDatabase(obj as ViewRowDatabase, edit);
  else if (type === ViewType.TABLE) return ViewTable.fromDatabase(obj as ViewTableDatabase, edit);
  else if (type === ViewType.TEXT) return ViewText.fromDatabase(obj as ViewTextDatabase);
  // NOTE: insert here other types of building-blocks

  return null;
}
