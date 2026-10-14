import AddBaseForm from "./AddBaseForm";

export default function AddBasePage() {
  return (
    <>
      <div className="title">
        <i className="fa fa-plus-square" aria-hidden="true"></i> Add new base
      </div>
      <hr />
      <div className="containerMain">
        <div className="content">
          <i className="fa fa-plus-circle" aria-hidden="true"></i> <b>New base</b>
          <hr />
          <AddBaseForm />
        </div>
      </div>
    </>
  );
}
