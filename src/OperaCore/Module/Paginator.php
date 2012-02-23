<?php

namespace OperaCore\Module;

/**
 * This class works as a Module and gives you the functionality of getting the HTML of a paginator
 * just passing it a small set of arguments. Don't forget to pass a Container in the Constructor
 */
class Paginator extends \OperaCore\Controller
{
	/**
	 * Returns the HTML of a paginator
	 *
	 * @param $base_url string the url to which we add the page number
	 * @param $total_rows int
	 * @param $current_page int Starting with 1 not 0
	 * @param $results_per_page int
	 * @return bool|string A string if the Template is correctly rendered
	 */
	public function getHtml( $base_url, $total_rows, $current_page, $results_per_page )
	{
		$pagination_params     = $this->paginate_get_array( $total_rows, $current_page, $results_per_page );
		$context['pagination'] = $pagination_params;
		$context['base_url']   = $base_url;

		return $this->render( 'pagination.html.twig', $context, parent::TEMPLATE_RENDER_RETURN );
	}

	/**
	 * Returns you the array of params that Paginator template expects to recieve.
	 *
	 * @param $total_rows int
	 * @param $current_page int
	 * @param $results_per_page int
	 * @return array The array of params that Paginator template expects to recieve
	 */
	protected function paginate_get_array( $total_rows, $current_page, $results_per_page )
	{
		$total_pages = floor( $total_rows / $results_per_page );

		$min_page = ( $current_page < 5 ) ? 1 : $current_page - 5;
		$max_page = ( $min_page + 9 < $total_pages ) ? $min_page + 9 : $total_pages;
		while ( ( $min_page > 1 ) && ( $max_page - $min_page + 1 < 9 ) )
		{
			$min_page--;
		}

		$array = array(
			'total_rows'       => $total_rows,
			'total_pages'      => $total_pages,
			'current_page'     => $current_page,
			'results_per_page' => $results_per_page
		);

		$array['previous_page'] = ( $current_page > 0 ) ? $current_page - 1 : NULL;
		$array['next_page']     = ( $current_page < $total_pages ) ? $current_page + 1 : NULL;
		$array['pages']         = range( $min_page, $max_page );

		return $array;
	}
}
