import React from 'react';
import { Provider } from 'react-redux'


export default class InputComponent extends React.Component {
  constructor(props) {
    super(props);

    this.state = {};
  }

  convertOptions(options) {
    if(typeof options === 'undefined' || options === null) {
      return null;
    }
    let htmlOptions = [];
    if(options.length) {
      options.forEach(function(value, key) {
        htmlOptions.push(<option key={key} value={key}>{value}</option>);
      });
    } else {
      for(let o in options) {
        htmlOptions.push(<option key={o} value={o}>{options[o]}</option>)
      }
    }
    return htmlOptions;
  }

  render() {
    const props = this.props;
    const { store } = this.context;
    let attribs = Object.assign({}, this.props.attribs);

    if(attribs.type === 'select') {
      delete attribs['type'];
      let selectOptions = this.convertOptions(attribs.options);
      delete attribs['options'];
      return (
        <select
          name={this.props.name}
          {...attribs}
          defaultValue={this.props.dbValue}>
            {selectOptions}
        </select>
      );
    }

    if(attribs.type === 'checkbox') {
      delete attribs['checkbox'];
      let checked = (this.props.dbValue) ? 'checked' : null;
      return (
        <input
          type="checkbox"
          name={this.props.name}
          {...attribs}
          defaultValue="1"
          defaultChecked={this.props.dbValue}
        />
      );
    }

    if(attribs.type === 'image') {
      return (
        <div className="upload_field">
          <input
            type="text"
            className="upload"
            id={attribs.id}
            name={this.props.name}
            defaultValue={this.props.dbValue}
          />
          <input type="button" className="browse" value="Select file" />
          <input type="button" className="clear" value="Clear" />
        </div>
      );
    }

    if(attribs.type === 'textarea') {
      delete attribs['type'];
      return (
        <textarea
          name={this.props.name}
          {...attribs}
          defaultValue={this.props.dbValue}>
        </textarea>
      );
    }
    return (
      <input
        name={this.props.name}
        {...attribs}
        defaultValue={this.props.dbValue}
      />
    );
  }
}

InputComponent.contextTypes = { store: React.PropTypes.object };
InputComponent.defaultProps = {};
